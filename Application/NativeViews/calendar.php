<?php
$pageTitle = 'Event calendar';
$first = new DateTimeImmutable($month . '-01');
$days = (int) $first->format('t');
$offset = (int) $first->format('N') - 1;
$eventsByDay = [];
foreach ($events as $event) $eventsByDay[(int) date('j', strtotime($event['event_date']))][] = $event;
$previous = $first->modify('-1 month')->format('Y-m');
$next = $first->modify('+1 month')->format('Y-m');
?>
<section class="page-intro"><div class="intro-inner"><span class="eyebrow">Owner scheduling</span><h1>Event calendar</h1><p>Confirmed events occupy their scheduled time. Pending reservations remain tentative and do not block the calendar.</p></div></section>
<section class="content-section"><div class="calendar-heading"><a class="button button-secondary" href="<?= rc_url('calendar', ['month' => $previous]) ?>">← Previous</a><h2><?= rc_e($first->format('F Y')) ?></h2><a class="button button-secondary" href="<?= rc_url('calendar', ['month' => $next]) ?>">Next →</a></div>
    <div class="calendar-scroll" tabindex="0" aria-label="Scrollable event calendar">
        <div class="calendar-grid calendar-days"><span>Monday</span><span>Tuesday</span><span>Wednesday</span><span>Thursday</span><span>Friday</span><span>Saturday</span><span>Sunday</span></div>
        <div class="calendar-grid"><?php for ($i = 0; $i < $offset; $i++): ?><div class="calendar-cell outside"></div><?php endfor; ?><?php for ($day = 1; $day <= $days; $day++): ?><div class="calendar-cell"><strong><?= $day ?></strong><?php foreach ($eventsByDay[$day] ?? [] as $event): ?><button type="button" class="calendar-event <?= rc_e(strtolower($event['schedule_status'])) ?>" data-dialog-title="<?= rc_e($event['client_name']) ?> · <?= rc_e($event['schedule_status']) ?>" data-dialog-text="<?= rc_e(substr($event['start_time'], 0, 5) . '–' . substr($event['end_time'], 0, 5) . ' · ' . $event['guest_count'] . ' guests · ' . $event['venue']) ?>" data-dialog-href="<?= rc_e(rc_url('orders', ['q' => (int) $event['order_id']])) ?>"><?= rc_e(substr($event['start_time'], 0, 5)) ?> <?= rc_e($event['client_name']) ?></button><?php endforeach; ?></div><?php endfor; ?></div>
    </div>
    <p class="muted">Select a calendar entry to open its reservation. Availability is checked again when the owner confirms a booking.</p>
</section>
