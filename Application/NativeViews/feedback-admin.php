<?php
$pageTitle = 'Feedback';
$feedbackStats = $feedbackStats ?: ['total' => 0, 'average' => 0, 'new_count' => 0, 'rating_1' => 0, 'rating_2' => 0, 'rating_3' => 0, 'rating_4' => 0, 'rating_5' => 0];
?>
<section class="owner-module">
  <header class="module-heading"><div><span class="eyebrow section-eyebrow">After-service responses</span><h1>Customer feedback</h1><p>Review each rating, comment, and customer photo before publishing the review to the customer page.</p></div></header>
  <div class="feedback-metrics">
    <article class="owner-metric"><span>Total feedback</span><strong><?= (int) $feedbackStats['total'] ?></strong></article>
    <article class="owner-metric"><span>Average rating</span><strong><?= number_format((float) $feedbackStats['average'], 1) ?> <small>/ 5</small></strong></article>
    <article class="owner-metric metric-warm"><span>Awaiting review</span><strong><?= (int) $feedbackStats['new_count'] ?></strong></article>
  </div>
  <section class="surface">
    <div class="surface-heading"><h2>Rating distribution</h2><span>All submitted feedback</span></div>
    <?php $totalRatings = max(1, (int) $feedbackStats['total']); for ($rating = 5; $rating >= 1; $rating--): $count = (int) $feedbackStats['rating_' . $rating]; ?>
      <div class="rating-row"><strong><?= $rating ?> stars</strong><div class="rating-track"><span style="width:<?= (int) round($count / $totalRatings * 100) ?>%"></span></div><span><?= $count ?> · <?= (int) round($count / $totalRatings * 100) ?>%</span></div>
    <?php endfor; ?>
  </section>
  <section class="surface">
    <div class="surface-heading"><h2>Submissions</h2><span><?= count($feedback) ?> records</span></div>
    <?php if ($feedback): ?><div class="feedback-review-grid"><?php foreach ($feedback as $entry): ?>
      <article class="surface feedback-review">
        <div class="feedback-review-heading"><div><strong><?= rc_e($entry['client_name']) ?></strong><span><?= rc_e($entry['client_email']) ?></span></div><time><?= rc_e($entry['submitted_at']) ?></time></div>
        <p><strong>#RZ-<?= (int) $entry['reservation_id'] ?> · <?= rc_e($entry['package_name'] ?: 'Catering service') ?></strong><br><?= rc_e($entry['event_date']) ?></p>
        <div class="feedback-stars" aria-label="<?= (int) $entry['rating'] ?> out of 5 stars"><?= str_repeat('★', (int) $entry['rating']) ?><span><?= str_repeat('☆', max(0, 5 - (int) $entry['rating'])) ?></span></div>
        <?php if (!empty($entry['comments'])): ?><p><?= nl2br(rc_e($entry['comments'])) ?></p><?php else: ?><p class="muted">No written comment.</p><?php endif; ?>
        <?php if (!empty($entry['image_path'])): ?><a href="/riz_catering/<?= rc_e(ltrim((string) $entry['image_path'], '/')) ?>" target="_blank" rel="noopener"><img class="feedback-review-image" src="/riz_catering/<?= rc_e(ltrim((string) $entry['image_path'], '/')) ?>" alt="Photo attached to customer feedback" loading="lazy"></a><?php endif; ?>
        <div class="feedback-review-actions"><span class="status status-<?= $entry['status'] === 'New' ? 'pending' : ($entry['status'] === 'Reviewed' ? 'completed' : 'cancelled') ?>"><?= rc_e($entry['status']) ?></span><?php if ($entry['status'] === 'New'): ?><form method="post" action="<?= rc_e(rc_endpoint()) ?>"><?= rc_csrf_field() ?><input type="hidden" name="action" value="feedback-reviewed"><input type="hidden" name="feedback_id" value="<?= (int) $entry['feedback_id'] ?>"><button class="button" type="submit">Approve and publish</button></form><?php endif; ?></div>
      </article>
    <?php endforeach; ?></div><?php else: ?><div class="empty-state"><strong>No feedback received yet</strong>Customer reviews will appear here after a completed reservation is reviewed.</div><?php endif; ?>
  </section>
</section>
