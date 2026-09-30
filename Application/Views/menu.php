<?php
	$searchTerm = trim((string) ($_GET['q'] ?? ''));
	$menuItems = array_values(array_filter($menu_items ?? [], static function ($item) use ($searchTerm) {
		if ($searchTerm === '') {
			return true;
		}
		$searchable = implode(' ', [$item['name'] ?? '', $item['description'] ?? '', $item['category'] ?? '']);
		return stripos($searchable, $searchTerm) !== false;
	}));
	$categories = array_values(array_unique(array_filter(array_map(static fn ($item) => trim((string) ($item['category'] ?? '')), $menu_items ?? []))));
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="theme-color" content="#cc0808">
	<title>Menu | Riz Catering</title>
	<style>
		:root { --brand: #cc0808; --brand-dark: #a80707; --ink: #25221f; --muted: #736d67; --paper: #fffdf9; --cream: #f6f0e6; --line: #e8e0d5; --green: #315d46; }
		* { box-sizing: border-box; }
		html { scroll-behavior: smooth; }
		body { margin: 0; background: var(--paper); color: var(--ink); font-family: "Trebuchet MS", "Segoe UI", sans-serif; }
		a { color: inherit; }
		.site-header { position: sticky; top: 0; z-index: 10; background: rgba(255,253,249,.97); border-bottom: 1px solid var(--line); }
		.header-inner { max-width: 1320px; min-height: 76px; margin: auto; padding: 12px 28px; display: flex; align-items: center; gap: 28px; }
		.brand { display: flex; align-items: center; gap: 10px; text-decoration: none; white-space: nowrap; }
		.brand-mark { width: 42px; aspect-ratio: 1; display: grid; place-items: center; border-radius: 50%; background: var(--brand); color: white; font: 700 24px Georgia, serif; }
		.brand-name { font: 700 21px Georgia, serif; line-height: 1; }
		.brand-name small { display: block; margin-top: 5px; color: var(--muted); font: 700 9px "Trebuchet MS", sans-serif; letter-spacing: 1.6px; text-transform: uppercase; }
		.main-nav { display: flex; flex: 1; justify-content: center; gap: 22px; }
		.main-nav a { color: #39332e; font-size: 13px; font-weight: 700; text-decoration: none; }
		.main-nav a.active { color: var(--brand); }
		.header-tools { display: flex; align-items: center; gap: 10px; }
		.search-form { display: flex; align-items: center; width: 200px; border: 1px solid var(--line); background: white; }
		.search-form input { width: 100%; min-width: 0; padding: 10px 11px; border: 0; outline: 0; font: inherit; font-size: 12px; }
		.search-form button, .bag-button { width: 38px; height: 38px; display: grid; place-items: center; border: 0; background: transparent; color: var(--ink); cursor: pointer; }
		.search-form button { color: var(--brand); font-size: 19px; }
		.bag-button { position: relative; border: 1px solid var(--line); font-size: 18px; }
		.bag-count { position: absolute; top: -6px; right: -6px; min-width: 17px; height: 17px; padding: 0 4px; display: grid; place-items: center; border-radius: 50%; background: var(--brand); color: white; font: 700 10px sans-serif; }
		.page-intro { background: var(--cream); }
		.intro-inner { max-width: 1320px; margin: auto; padding: 52px 28px 45px; }
		.eyebrow { color: var(--brand); font-size: 11px; font-weight: 700; letter-spacing: 1.7px; text-transform: uppercase; }
		.page-intro h1 { margin: 10px 0 8px; font: 700 46px/1.05 Georgia, serif; }
		.page-intro p { max-width: 620px; margin: 0; color: var(--muted); font-size: 14px; line-height: 1.65; }
		.catalog { max-width: 1320px; margin: auto; padding: 38px 28px 72px; display: grid; grid-template-columns: 210px minmax(0, 1fr); gap: 38px; }
		.sidebar { align-self: start; position: sticky; top: 100px; }
		.sidebar h2 { margin: 0 0 15px; font: 700 19px Georgia, serif; }
		.filter-list { display: grid; gap: 3px; margin-bottom: 27px; }
		.filter-button { padding: 9px 10px; border: 0; border-left: 2px solid transparent; background: transparent; color: #554e47; text-align: left; font: inherit; font-size: 13px; cursor: pointer; }
		.filter-button:hover, .filter-button.active { border-left-color: var(--brand); background: var(--cream); color: var(--brand); font-weight: 700; }
		.catalog-top { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 19px; }
		.catalog-top h2 { margin: 0; font: 700 24px Georgia, serif; }
		.result-count { color: var(--muted); font-size: 12px; }
		.product-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 22px 17px; }
		.product-card { min-width: 0; border: 1px solid var(--line); background: white; animation: reveal .4s ease both; }
		.product-image-wrap { position: relative; aspect-ratio: 1.24; overflow: hidden; background: var(--cream); }
		.product-image { width: 100%; height: 100%; object-fit: cover; transition: transform .4s ease; }
		.product-card:hover .product-image { transform: scale(1.035); }
		.product-category { position: absolute; top: 11px; left: 11px; padding: 6px 8px; background: rgba(255,253,249,.93); color: var(--brand); font-size: 9px; font-weight: 700; letter-spacing: .8px; text-transform: uppercase; }
		.product-info { padding: 15px; }
		.product-info h3 { margin: 0 0 7px; font: 700 19px/1.2 Georgia, serif; }
		.product-info p { min-height: 38px; margin: 0 0 12px; color: var(--muted); font-size: 12px; line-height: 1.55; }
		.diet-tags { display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 13px; }
		.diet-tag { padding: 4px 7px; background: #e9f0e9; color: var(--green); font-size: 10px; }
		.product-bottom { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
		.price { font-size: 14px; font-weight: 700; white-space: nowrap; }
		.add-button, .submit-button { min-height: 39px; padding: 0 13px; border: 0; background: var(--brand); color: white; font: inherit; font-size: 12px; font-weight: 700; cursor: pointer; transition: background .16s ease; }
		.add-button:hover, .submit-button:hover { background: var(--brand-dark); }
		.empty-state { padding: 52px 24px; border: 1px dashed #d6cbbd; color: var(--muted); text-align: center; line-height: 1.7; }
		.empty-state strong { display: block; margin-bottom: 7px; color: var(--ink); font: 700 23px Georgia, serif; }
		.flash-message { margin: 18px 0 0; padding: 13px 15px; font-size: 13px; line-height: 1.5; }
		.flash-success { background: #e8f1e9; color: #28513a; }
		.flash-error { background: #f8e5e2; color: #8d1d13; }
		.booking-section { background: #f6f0e6; }
		.booking-inner { max-width: 950px; margin: auto; padding: 75px 28px; }
		.booking-inner h2 { margin: 9px 0; font: 700 38px Georgia, serif; }
		.booking-intro { margin: 0 0 26px; color: var(--muted); font-size: 14px; line-height: 1.65; }
		.booking-form { padding: 25px; border: 1px solid var(--line); background: white; }
		.selected-summary { margin-bottom: 22px; padding: 15px; background: var(--cream); }
		.selected-summary h3 { margin: 0 0 9px; font: 700 17px Georgia, serif; }
		.selected-lines { margin: 0; padding-left: 19px; color: #514a43; font-size: 13px; line-height: 1.8; }
		.selected-total { display: flex; justify-content: space-between; margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--line); font-weight: 700; }
		.customer-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
		.field { min-width: 0; display: grid; gap: 7px; }
		.field-wide { grid-column: 1 / -1; }
		.field label { font-size: 12px; font-weight: 700; }
		.input-field { width: 100%; min-width: 0; min-height: 43px; padding: 10px 11px; border: 1px solid #d9d0c5; border-radius: 0; background: white; color: var(--ink); font: inherit; font-size: 13px; }
		.input-field:focus { outline: 2px solid #cc080833; border-color: var(--brand); }
		.payment-note { grid-column: 1 / -1; margin: 0; color: var(--muted); font-size: 12px; line-height: 1.5; }
		.submit-button { width: 100%; min-height: 47px; margin-top: 21px; font-size: 13px; }
		.site-footer { padding: 27px 28px; background: #191715; color: #d8d0c7; }
		.footer-inner { max-width: 1264px; margin: auto; display: flex; justify-content: space-between; gap: 20px; font-size: 12px; }
		.drawer-scrim { position: fixed; inset: 0; z-index: 20; background: #18141088; opacity: 0; pointer-events: none; transition: opacity .2s ease; }
		.bag-drawer { position: fixed; top: 0; right: 0; bottom: 0; z-index: 21; width: min(410px, 100%); padding: 22px; display: flex; flex-direction: column; background: var(--paper); transform: translateX(100%); transition: transform .24s ease; }
		.bag-open .drawer-scrim { opacity: 1; pointer-events: auto; }
		.bag-open .bag-drawer { transform: translateX(0); }
		.drawer-heading { display: flex; align-items: center; justify-content: space-between; padding-bottom: 16px; border-bottom: 1px solid var(--line); }
		.drawer-heading h2 { margin: 0; font: 700 25px Georgia, serif; }
		.close-drawer { width: 38px; height: 38px; border: 1px solid var(--line); background: white; font-size: 20px; cursor: pointer; }
		.bag-lines { flex: 1; overflow-y: auto; padding: 12px 0; }
		.bag-line { display: grid; grid-template-columns: 1fr auto; gap: 12px; padding: 14px 0; border-bottom: 1px solid var(--line); }
		.bag-line h3 { margin: 0 0 5px; font: 700 16px Georgia, serif; }
		.bag-line p { margin: 0; color: var(--muted); font-size: 12px; }
		.quantity-tools { display: flex; align-items: center; gap: 8px; }
		.quantity-tools button { width: 28px; height: 28px; border: 1px solid var(--line); background: white; cursor: pointer; }
		.bag-total { display: flex; justify-content: space-between; padding: 15px 0; border-top: 1px solid var(--line); font-weight: 700; }
		.drawer-hint { color: var(--muted); font-size: 11px; line-height: 1.5; }
		@keyframes reveal { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
		@media (max-width: 1050px) { .main-nav { gap: 13px; } .product-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
		@media (max-width: 760px) { .header-inner { flex-wrap: wrap; gap: 12px; padding: 12px 18px; } .main-nav { order: 3; flex-basis: 100%; justify-content: flex-start; overflow: auto; padding: 5px 0; } .header-tools { margin-left: auto; } .search-form { width: 42px; } .search-form input { display: none; } .intro-inner { padding: 42px 20px; } .page-intro h1 { font-size: 39px; } .catalog { grid-template-columns: 1fr; gap: 20px; padding: 28px 20px 55px; } .sidebar { position: static; } .filter-list { display: flex; overflow: auto; padding-bottom: 4px; } .filter-button { flex: 0 0 auto; border: 1px solid var(--line); } .filter-button:hover, .filter-button.active { border-color: var(--brand); } .booking-inner { padding: 55px 20px; } }
		@media (max-width: 520px) { .product-grid { grid-template-columns: 1fr; } .product-image-wrap { aspect-ratio: 1.5; } .booking-form { padding: 17px; } .customer-fields { grid-template-columns: 1fr; } .field-wide, .payment-note { grid-column: auto; } .footer-inner { flex-direction: column; } }
		@media (prefers-reduced-motion: reduce) { *, *::before, *::after { scroll-behavior: auto !important; animation-duration: .01ms !important; transition-duration: .01ms !important; } }
	</style>
</head>
<body>
	<header class="site-header">
		<div class="header-inner">
			<a class="brand" href="<?= base_url('/') ?>"><span class="brand-mark">R</span><span class="brand-name">Riz Catering<small>Gather around good food</small></span></a>
			<nav class="main-nav" aria-label="Main navigation"><a class="active" href="#catalog">Menu</a><a href="#catalog">Food Trays</a><a href="#catalog">Packed Meals</a><a href="#catalog">Packages</a><a href="<?= base_url('/#catering') ?>">Catering</a><a href="<?= base_url('/#inquiry') ?>">Contact</a></nav>
			<div class="header-tools"><form class="search-form" action="<?= base_url('/menu') ?>" method="get" role="search"><input name="q" type="search" value="<?= esc($searchTerm) ?>" placeholder="Search food" aria-label="Search food"><button type="submit" aria-label="Search">⌕</button></form><button class="bag-button" id="open-bag" type="button" aria-label="Open shopping bag">🛍<span class="bag-count" id="bag-count">0</span></button></div>
		</div>
	</header>
	<main>
		<section class="page-intro"><div class="intro-inner"><span class="eyebrow">Prepared with care, shared with everyone</span><h1>Find your next favorite.</h1><p>Browse the dishes, build your bag, and send us your event details. The owner will review your request and contact you to arrange payment.</p>
			<?php if ($success = session()->getFlashdata('success')): ?><p class="flash-message flash-success" role="status" id="order-success"><?= esc($success) ?></p><?php endif; ?>
			<?php if ($error = session()->getFlashdata('error')): ?><p class="flash-message flash-error" role="alert"><?= esc($error) ?></p><?php endif; ?>
		</div></section>
		<section class="catalog" id="catalog" aria-label="Menu catalog">
			<aside class="sidebar"><h2>Browse menu</h2><div class="filter-list" aria-label="Menu categories"><button class="filter-button active" type="button" data-filter="all">All dishes</button><?php foreach ($categories as $category): ?><button class="filter-button" type="button" data-filter="<?= esc(strtolower($category), 'attr') ?>"><?= esc($category) ?></button><?php endforeach; ?></div><h2>Dietary options</h2><div class="filter-list"><button class="filter-button" type="button" data-filter="vegan">Vegan</button><button class="filter-button" type="button" data-filter="gf">Gluten-free</button></div></aside>
			<div>
				<div class="catalog-top"><h2>Menu selections</h2><span class="result-count" id="result-count"><?= count($menuItems) ?> dishes</span></div>
				<?php if ($menuItems): ?>
				<div class="product-grid" id="product-grid">
					<?php $foodImages = ['photo-1547592180-85f173990554', 'photo-1546069901-ba9599a7e63c', 'photo-1555939594-58d7cb561ad1', 'photo-1512621776951-a57141f2eefd']; ?>
					<?php foreach ($menuItems as $index => $item): ?>
						<?php $categoryKey = strtolower((string) ($item['category'] ?? '')); ?>
						<article class="product-card" data-id="<?= esc((string) $item['id'], 'attr') ?>" data-name="<?= esc($item['name'], 'attr') ?>" data-price="<?= esc((string) $item['price'], 'attr') ?>" data-category="<?= esc($categoryKey, 'attr') ?>" data-vegan="<?= !empty($item['is_vegan']) ? '1' : '0' ?>" data-gf="<?= !empty($item['is_gluten_free']) ? '1' : '0' ?>" data-search="<?= esc(strtolower($item['name'] . ' ' . ($item['description'] ?? '') . ' ' . ($item['category'] ?? '')), 'attr') ?>">
							<div class="product-image-wrap"><img class="product-image" src="https://images.unsplash.com/<?= $foodImages[$index % count($foodImages)] ?>?auto=format&amp;fit=crop&amp;w=800&amp;q=78" alt="<?= esc($item['name']) ?>" loading="lazy"><span class="product-category"><?= esc($item['category'] ?: 'Menu') ?></span></div>
							<div class="product-info"><h3><?= esc($item['name']) ?></h3><p><?= esc($item['description'] ?: 'Prepared fresh for your table.') ?></p><div class="diet-tags"><?php if (!empty($item['is_vegan'])): ?><span class="diet-tag">Vegan</span><?php endif; ?><?php if (!empty($item['is_gluten_free'])): ?><span class="diet-tag">Gluten-free</span><?php endif; ?></div><div class="product-bottom"><span class="price">&#8369;<?= number_format((float) $item['price'], 2) ?></span><button class="add-button" type="button" data-add>＋ Add to Bag</button></div></div>
						</article>
					<?php endforeach; ?>
				</div>
				<p class="empty-state" id="filter-empty" hidden><strong>No dishes found</strong>Try another category or search term.</p>
				<?php else: ?>
				<div class="empty-state"><strong>The menu is being prepared.</strong>There are no dishes listed yet. Please check back soon.</div>
				<?php endif; ?>
			</div>
		</section>
		<section class="booking-section" id="booking"><div class="booking-inner"><span class="eyebrow">One last step</span><h2>Send your booking request</h2><p class="booking-intro">Share your contact and event details. We will confirm availability and follow up to complete your order. Payment is arranged with the owner; no payment is collected online.</p>
			<form id="booking-form" class="booking-form" action="<?= base_url('/menu/submitOrder') ?>" method="POST">
				<?= csrf_field() ?>
				<input type="hidden" name="selected_items" id="selected-items" value="[]">
				<div class="selected-summary"><h3>Your bag</h3><ul class="selected-lines" id="selected-lines"><li>No dishes selected yet.</li></ul><div class="selected-total"><span>Estimated total</span><span id="selected-total">&#8369;0.00</span></div><div class="summary-line"><span>50% deposit to confirm</span><strong id="deposit-due">&#8369;0.00</strong></div><div class="summary-line"><span>Balance after deposit</span><strong id="balance-after-deposit">&#8369;0.00</strong></div></div>
				<div class="customer-fields">
					<div class="field"><label for="client-name">Full name *</label><input class="input-field" id="client-name" type="text" name="client_name" placeholder="Juan Dela Cruz" autocomplete="name" required maxlength="120" value="<?= esc((string) old('client_name')) ?>"></div>
					<div class="field"><label for="client-email">Email address *</label><input class="input-field" id="client-email" type="email" name="client_email" placeholder="juan@example.com" autocomplete="email" required maxlength="254" value="<?= esc((string) old('client_email')) ?>"></div>
					<div class="field"><label for="client-mobile">Mobile number *</label><input class="input-field" id="client-mobile" type="tel" name="client_mobile" placeholder="09XX XXX XXXX" autocomplete="tel" required maxlength="30" value="<?= esc((string) old('client_mobile')) ?>"></div>
					<div class="field"><label for="event-date">Date of event *</label><input class="input-field" id="event-date" type="date" name="event_date" required value="<?= esc((string) old('event_date')) ?>"></div>
					<div class="field"><label for="guest-count">Number of guests *</label><input class="input-field" id="guest-count" type="number" name="guest_count" min="1" max="10000" required placeholder="Number of guests" value="<?= esc((string) old('guest_count', '50')) ?>"></div>
					<div class="field"><label for="event-start">Event start *</label><input class="input-field" id="event-start" type="time" name="event_start_time" required value="<?= esc((string) old('event_start_time')) ?>"></div>
					<div class="field"><label for="event-end">Event end *</label><input class="input-field" id="event-end" type="time" name="event_end_time" required value="<?= esc((string) old('event_end_time')) ?>"></div>
					<div class="field"><label for="dining-time">Dining time *</label><input class="input-field" id="dining-time" type="time" name="dining_time" required value="<?= esc((string) old('dining_time')) ?>"></div>
					<div class="field"><label for="occasion">Occasion</label><input class="input-field" id="occasion" type="text" name="occasion" maxlength="100" placeholder="Birthday, wedding, corporate event" value="<?= esc((string) old('occasion')) ?>"></div>
					<div class="field field-wide"><label for="delivery-address">Venue *</label><input class="input-field" id="delivery-address" type="text" name="delivery_address" placeholder="Venue name and complete address" autocomplete="street-address" required maxlength="500" value="<?= esc((string) old('delivery_address')) ?>"></div>
					<div class="field"><label for="motif">Motif or theme</label><input class="input-field" id="motif" type="text" name="motif" maxlength="160" placeholder="Colors or theme" value="<?= esc((string) old('motif')) ?>"></div>
					<div class="field"><label for="payment-method">Preferred payment method *</label><select class="input-field" id="payment-method" name="payment_method" required><option value="Cash" <?= old('payment_method', 'Cash') === 'Cash' ? 'selected' : '' ?>>Cash</option><option value="GCash" <?= old('payment_method') === 'GCash' ? 'selected' : '' ?>>GCash</option><option value="Bank Transfer" <?= old('payment_method') === 'Bank Transfer' ? 'selected' : '' ?>>Bank Transfer</option><option value="Check" <?= old('payment_method') === 'Check' ? 'selected' : '' ?>>Check</option></select></div>
					<div class="field field-wide"><label for="special-requests">Special requests</label><textarea class="input-field" id="special-requests" name="special_requests" maxlength="5000" placeholder="Dietary needs, setup details, or other event requirements"><?= esc((string) old('special_requests')) ?></textarea></div>
					<p class="payment-note">A 50% down payment is required before confirmation. The owner records payments manually; this website does not process GCash, bank, cash, or check payments.</p>
				</div>
				<button class="submit-button" type="submit">Submit booking request</button>
			</form>
		</div></section>
	</main>
	<footer class="site-footer"><div class="footer-inner"><span>Riz Catering</span><span>Good food, shared generously.</span><a href="<?= base_url('/') ?>">Back to home</a></div></footer>
	<div class="drawer-scrim" id="drawer-scrim"></div>
	<aside class="bag-drawer" id="bag-drawer" aria-label="Shopping bag" aria-hidden="true"><div class="drawer-heading"><h2>Your bag</h2><button class="close-drawer" id="close-bag" type="button" aria-label="Close shopping bag">×</button></div><div class="bag-lines" id="bag-lines"></div><div class="bag-total"><span>Estimated total</span><span id="drawer-total">&#8369;0.00</span></div><p class="drawer-hint">Prices are based on menu listings. Availability and final details are confirmed by the owner.</p><a class="add-button" href="#booking" id="review-order" style="display:grid;place-items:center;text-decoration:none">Review booking details</a></aside>
	<script>
		(() => {
			const storageKey = 'riz-catering-bag';
			const bagCount = document.getElementById('bag-count');
			const bagLines = document.getElementById('bag-lines');
			const selectedLines = document.getElementById('selected-lines');
			const selectedItems = document.getElementById('selected-items');
			const selectedTotal = document.getElementById('selected-total');
			const depositDue = document.getElementById('deposit-due');
			const balanceAfterDeposit = document.getElementById('balance-after-deposit');
			const drawerTotal = document.getElementById('drawer-total');
			const bookingForm = document.getElementById('booking-form');
			let bag = [];
			try { bag = JSON.parse(localStorage.getItem(storageKey) || '[]'); } catch { bag = []; }
			if (!Array.isArray(bag)) bag = [];

			const money = (amount) => `₱${Number(amount).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
			const persist = () => localStorage.setItem(storageKey, JSON.stringify(bag));
			const updateSummary = () => {
				const quantity = bag.reduce((sum, item) => sum + item.quantity, 0);
				const totalCents = bag.reduce((sum, item) => sum + Math.round(item.price * 100) * item.quantity, 0);
				const total = totalCents / 100;
				const depositCents = Math.ceil(totalCents / 2);
				bagCount.textContent = quantity;
				bagCount.setAttribute('aria-label', `${quantity} items`);
				selectedTotal.textContent = money(total);
				depositDue.textContent = money(depositCents / 100);
				balanceAfterDeposit.textContent = money((totalCents - depositCents) / 100);
				drawerTotal.textContent = money(total);
				selectedItems.value = JSON.stringify(bag.map(({ id, quantity: itemQuantity }) => ({ id, quantity: itemQuantity })));
				selectedLines.replaceChildren();
				if (bag.length === 0) {
					const empty = document.createElement('li');
					empty.textContent = 'No dishes selected yet.';
					selectedLines.append(empty);
				} else {
					bag.forEach((item) => {
						const line = document.createElement('li');
						line.textContent = `${item.name} × ${item.quantity} · ${money(item.price * item.quantity)}`;
						selectedLines.append(line);
					});
				}
				bagLines.replaceChildren();
				bag.forEach((item) => {
					const line = document.createElement('div');
					line.className = 'bag-line';
					const details = document.createElement('div');
					const name = document.createElement('h3');
					name.textContent = item.name;
					const price = document.createElement('p');
					price.textContent = money(item.price * item.quantity);
					details.append(name, price);
					const tools = document.createElement('div');
					tools.className = 'quantity-tools';
					const decrease = document.createElement('button');
					decrease.type = 'button';
					decrease.textContent = '−';
					decrease.setAttribute('aria-label', `Remove one ${item.name}`);
					decrease.addEventListener('click', () => changeQuantity(item.id, -1));
					const count = document.createElement('span');
					count.textContent = item.quantity;
					const increase = document.createElement('button');
					increase.type = 'button';
					increase.textContent = '+';
					increase.setAttribute('aria-label', `Add one ${item.name}`);
					increase.addEventListener('click', () => changeQuantity(item.id, 1));
					tools.append(decrease, count, increase);
					line.append(details, tools);
					bagLines.append(line);
				});
			};
			const changeQuantity = (itemId, amount) => {
				const item = bag.find((entry) => entry.id === itemId);
				if (!item) return;
				item.quantity += amount;
				if (item.quantity <= 0) bag = bag.filter((entry) => entry.id !== itemId);
				if (item.quantity > 99) item.quantity = 99;
				persist();
				updateSummary();
			};
			document.querySelectorAll('[data-add]').forEach((button) => button.addEventListener('click', () => {
				const card = button.closest('.product-card');
				const itemId = Number(card.dataset.id);
				const existing = bag.find((entry) => entry.id === itemId);
				if (existing) existing.quantity = Math.min(99, existing.quantity + 1);
				else bag.push({ id: itemId, name: card.dataset.name, price: Number(card.dataset.price), quantity: 1 });
				persist();
				updateSummary();
				button.textContent = 'Added';
				window.setTimeout(() => { button.textContent = '＋ Add to Bag'; }, 1000);
			}));

			const filters = document.querySelectorAll('.filter-button');
			let activeCategory = 'all';
			let activeDiet = '';
			const applyFilters = () => {
				const term = new URLSearchParams(window.location.search).get('q')?.toLowerCase() || '';
				let visible = 0;
				document.querySelectorAll('.product-card').forEach((card) => {
					const categoryMatch = activeCategory === 'all' || card.dataset.category === activeCategory;
					const dietMatch = !activeDiet || card.dataset[activeDiet] === '1';
					const searchMatch = !term || card.dataset.search.includes(term);
					card.hidden = !(categoryMatch && dietMatch && searchMatch);
					if (!card.hidden) visible += 1;
				});
				const count = document.getElementById('result-count');
				const empty = document.getElementById('filter-empty');
				if (count) count.textContent = `${visible} ${visible === 1 ? 'dish' : 'dishes'}`;
				if (empty) empty.hidden = visible !== 0;
			};
			filters.forEach((button) => button.addEventListener('click', () => {
				const value = button.dataset.filter;
				if (value === 'vegan' || value === 'gf') {
					activeDiet = activeDiet === value ? '' : value;
					filters.forEach((filter) => { if (filter.dataset.filter === 'vegan' || filter.dataset.filter === 'gf') filter.classList.toggle('active', filter.dataset.filter === activeDiet); });
				} else {
					activeCategory = value;
					filters.forEach((filter) => { if (filter.dataset.filter !== 'vegan' && filter.dataset.filter !== 'gf') filter.classList.toggle('active', filter === button); });
				}
				applyFilters();
			}));

			const setDrawer = (open) => {
				document.body.classList.toggle('bag-open', open);
				document.getElementById('bag-drawer').setAttribute('aria-hidden', String(!open));
			};
			document.getElementById('open-bag').addEventListener('click', () => setDrawer(true));
			document.getElementById('close-bag').addEventListener('click', () => setDrawer(false));
			document.getElementById('drawer-scrim').addEventListener('click', () => setDrawer(false));
			document.getElementById('review-order').addEventListener('click', () => setDrawer(false));
			bookingForm.addEventListener('submit', (event) => {
				if (bag.length === 0) {
					event.preventDefault();
					setDrawer(true);
					return;
				}
				selectedItems.value = JSON.stringify(bag.map(({ id, quantity }) => ({ id, quantity })));
			});
			if (document.getElementById('order-success')) {
				bag = [];
				persist();
			}
			updateSummary();
			applyFilters();
		})();
	</script>
</body>
</html>
