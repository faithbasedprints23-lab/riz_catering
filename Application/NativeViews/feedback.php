<?php $pageTitle = 'Customer feedback'; ?>
<section class="page-intro"><div class="intro-inner"><span class="eyebrow">Real customer experiences</span><h1>Customer feedback</h1><p>Read reviews from customers and share how your completed Riz Catering order went.</p></div></section>
<section class="content-section feedback-page">
  <?php if (($user['role'] ?? '') === 'Customer'): ?>
    <section class="surface feedback-form-surface"><div class="surface-heading"><h2>Share your experience</h2><span>One review per completed order</span></div>
      <?php if ($eligibleOrders): ?>
        <form method="post" action="<?= rc_e(rc_endpoint()) ?>" enctype="multipart/form-data" class="feedback-form"><?= rc_csrf_field() ?><input type="hidden" name="action" value="feedback">
          <div class="field"><label for="feedback-order">Completed order</label><select class="input-field" id="feedback-order" name="order_id" required><option value="">Choose an order</option><?php foreach ($eligibleOrders as $eligibleOrder): ?><option value="<?= (int) $eligibleOrder['id'] ?>" <?= (int) ($_GET['order_id'] ?? 0) === (int) $eligibleOrder['id'] ? 'selected' : '' ?>>#RZ-<?= (int) $eligibleOrder['id'] ?> · <?= rc_e($eligibleOrder['package_name'] ?: 'Catering service') ?> · <?= rc_e($eligibleOrder['event_date']) ?></option><?php endforeach; ?></select></div>
          <fieldset class="feedback-rating"><legend>Your rating</legend><div class="star-picker" role="radiogroup" aria-label="Rating from one to five stars"><?php for ($star = 5; $star >= 1; $star--): ?><input type="radio" id="feedback-rating-<?= $star ?>" name="rating" value="<?= $star ?>" required><label for="feedback-rating-<?= $star ?>" title="<?= $star ?> star<?= $star === 1 ? '' : 's' ?>">★</label><?php endfor; ?></div></fieldset>
          <div class="field"><label for="feedback-comments">Your comments <span class="muted">(optional)</span></label><textarea class="input-field" id="feedback-comments" name="comments" maxlength="3000" rows="4" placeholder="Tell us about your experience"></textarea></div>
          <div class="field"><label for="feedback-image">Add a photo <span class="muted">(optional, JPG, PNG, or WebP up to 5 MB)</span></label><input class="input-field" id="feedback-image" name="image" type="file" accept="image/jpeg,image/png,image/webp"></div>
          <button class="button" type="submit">Submit feedback</button>
        </form>
      <?php else: ?><div class="empty-state"><strong>No completed order is available for review</strong>After the owner marks one of your reservations Completed, you can leave one review for it here.</div><?php endif; ?>
    </section>
  <?php elseif (!$user): ?>
    <section class="surface feedback-login"><h2>Feedback is for completed bookings</h2><p>Create an account or sign in to review a booking linked to your customer account after its status is Completed. Orders without an eligible completed booking cannot submit feedback.</p><div class="action-row"><a class="button" href="<?= rc_url('login') ?>">Customer sign in</a><a class="button button-secondary" href="<?= rc_url('register') ?>">Create account</a></div></section>
  <?php endif; ?>

  <section class="feedback-reviews"><div class="surface-heading"><h2>Customer reviews</h2><span><?= count($feedbackEntries) ?> published</span></div>
    <?php if ($feedbackEntries): ?><div class="feedback-review-grid"><?php foreach ($feedbackEntries as $entry): $reviewerFirstName = explode(' ', trim((string) $entry['reviewer_name']))[0] ?? 'Customer'; ?>
      <article class="surface feedback-review"><div class="feedback-review-heading"><div><strong><?= rc_e($reviewerFirstName) ?></strong><span><?= rc_e($entry['package_name'] ?: 'Catering service') ?></span></div><time><?= rc_e(date('M j, Y', strtotime((string) $entry['submitted_at']))) ?></time></div>
        <div class="feedback-stars" aria-label="<?= (int) $entry['rating'] ?> out of 5 stars"><?= str_repeat('★', (int) $entry['rating']) ?><span><?= str_repeat('☆', 5 - (int) $entry['rating']) ?></span></div>
        <?php if (!empty($entry['comments'])): ?><p><?= nl2br(rc_e($entry['comments'])) ?></p><?php endif; ?>
        <?php if (!empty($entry['image_path'])): ?><img class="feedback-review-image" src="/riz_catering/<?= rc_e(ltrim((string) $entry['image_path'], '/')) ?>" alt="Photo shared with customer feedback" loading="lazy"><?php endif; ?>
      </article>
    <?php endforeach; ?></div><?php else: ?><div class="empty-state"><strong>No published reviews yet</strong>Customer reviews will appear here after owner review.</div><?php endif; ?>
  </section>
</section>
