<?php $pageTitle = 'Food for every gathering'; ?>
<section class="hero" aria-labelledby="hero-title"><div class="hero-content">
  <span class="eyebrow">Food made for sharing</span>
  <h1 id="hero-title">A little more joy at every table.</h1>
  <p>Explore the catering menu and book your event with or without an account. An account adds booking notifications and lets you review completed orders.</p>
  <div class="action-row"><a class="button" href="<?= rc_url('menu') ?>">Explore the menu</a><a class="button button-quiet" href="<?= rc_url('reservation') ?>">Order or reserve</a><?php if (!$user): ?>
    <a class="button button-quiet" href="<?= rc_url('register') ?>">Create account</a><?php else: ?>
      <a class="button button-quiet" href="<?= rc_url('dashboard') ?>">Open my dashboard</a><?php endif; ?>
    </div></div><span class="hero-caption"><?= rc_e($businessName ?? 'Riz Catering Services') ?></span></section>
<section class="content-section"><div class="section-heading">
  <div><span class="eyebrow section-eyebrow">The menu</span>
  <h2>Choose how to gather.</h2></div><div><p>Browse the current offerings managed by Riz Catering. Package prices and selections come from the menu records.</p>
  <a class="text-link" href="<?= rc_url('menu') ?>">View all offerings →</a></div></div>
  <div class="category-grid"><a class="category-tile" href="<?= rc_url('menu', ['category' => 'Food Trays']) ?>">
    <img src="https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=1000&q=80" alt="A colorful shared meal">
    <span class="category-copy"><span>For the whole table</span><h3>Food Trays</h3></span></a>
    <a class="category-tile" href="<?= rc_url('menu', ['category' => 'Packed Meals']) ?>">
      <img src="https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=1000&q=80" alt="A prepared individual meal">
      <span class="category-copy"><span>Easy to serve</span><h3>Packed Meals</h3></span></a>
      <a class="category-tile" href="<?= rc_url('menu', ['category' => 'Packages']) ?>">  
        <img src="https://images.unsplash.com/photo-1511795409834-ef04bbd61622?auto=format&fit=crop&w=1000&q=80" alt="A table set for a celebration">
        <span class="category-copy"><span>Made for milestones</span><h3>Party Packages</h3></span></a></div>
</section>
<section class="packed-band">
  <div class="packed-inner"><img class="packed-image" src="/riz_catering/assets/riz_catering_pic1.jpg?v=20260926-2" alt="Riz Catering food prepared for a group">
  <div class="packed-copy"><span class="eyebrow section-eyebrow">Plan together</span><h2>From menu to event day.</h2>
  <p>Select individual dishes, packed meals, a buffet set, or a package, then submit your event details. You can check out as a guest or sign in to keep booking updates in your account.</p>
  <a class="button" href="<?= rc_url('reservation') ?>">Start an order</a>
</div></div></section>
<section class="catering" id="catering"><div class="catering-inner"><div class="catering-copy"><span class="eyebrow">Your event, thoughtfully catered</span><h2>One place for the whole booking.</h2><p>Customer accounts receive booking notifications and can review an order after it is completed. Feedback requires an eligible completed booking.</p><div class="action-row"><a class="button" href="<?= $user ? rc_url('dashboard') : rc_url('register') ?>"><?= $user ? 'Open my account' : 'Create account' ?></a><a class="button button-quiet" href="<?= rc_url('menu') ?>">Browse menu</a></div></div><img class="catering-image" src="https://images.unsplash.com/photo-1511795409834-ef04bbd61622?auto=format&fit=crop&w=1200&q=80" alt="A warmly set table ready for an event"></div></section>
