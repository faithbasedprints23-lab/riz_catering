<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="theme-color" content="#cc0808">
	<title>Riz Catering | Food for every gathering</title>
	<style>
		:root {
			--brand: #cc0808;
			--brand-dark: #a80707;
			--ink: #25221f;
			--muted: #736d67;
			--paper: #fffdf9;
			--cream: #f6f0e6;
			--line: #e8e0d5;
			--green: #315d46;
		}
		* { box-sizing: border-box; }
		html { scroll-behavior: smooth; }
		body { margin: 0; background: var(--paper); color: var(--ink); font-family: "Trebuchet MS", "Segoe UI", sans-serif; }
		a { color: inherit; }
		.site-header { position: sticky; top: 0; z-index: 10; background: rgba(255,253,249,.97); border-bottom: 1px solid var(--line); }
		.header-inner { max-width: 1320px; min-height: 78px; margin: auto; padding: 12px 28px; display: flex; align-items: center; gap: 30px; }
		.brand { display: flex; align-items: center; gap: 10px; text-decoration: none; white-space: nowrap; }
		.brand-mark { width: 42px; aspect-ratio: 1; display: grid; place-items: center; border-radius: 50%; background: var(--brand); color: white; font-family: Georgia, serif; font-size: 24px; font-weight: 700; }
		.brand-name { font-family: Georgia, serif; font-size: 21px; font-weight: 700; line-height: 1; }
		.brand-name small { display: block; margin-top: 5px; color: var(--muted); font: 700 9px "Trebuchet MS", sans-serif; letter-spacing: 1.6px; text-transform: uppercase; }
		.main-nav { display: flex; align-items: center; justify-content: center; flex: 1; gap: 19px; }
		.main-nav a, .nav-group summary { color: #39332e; font-size: 13px; font-weight: 700; text-decoration: none; cursor: pointer; list-style: none; white-space: nowrap; }
		.nav-group { position: relative; }
		.nav-group summary::-webkit-details-marker { display: none; }
		.nav-group summary::after { content: "⌄"; margin-left: 5px; color: var(--brand); }
		.nav-dropdown { position: absolute; top: 30px; left: -15px; min-width: 205px; padding: 8px; background: white; border: 1px solid var(--line); box-shadow: 0 14px 36px #32241a1a; }
		.nav-dropdown a { display: block; padding: 10px; border-radius: 3px; }
		.nav-dropdown a:hover { background: var(--cream); color: var(--brand); }
		.header-tools { display: flex; align-items: center; gap: 10px; }
		.search-form { display: flex; align-items: center; width: 190px; border: 1px solid var(--line); background: white; }
		.search-form input { width: 100%; min-width: 0; padding: 10px 11px; border: 0; outline: 0; font: inherit; font-size: 12px; }
		.search-form button, .bag-button { width: 38px; height: 38px; display: grid; place-items: center; border: 0; background: transparent; color: var(--ink); cursor: pointer; }
		.search-form button { color: var(--brand); font-size: 19px; }
		.bag-button { position: relative; border: 1px solid var(--line); font-size: 18px; }
		.bag-count { position: absolute; top: -6px; right: -6px; min-width: 17px; height: 17px; padding: 0 4px; display: grid; place-items: center; border-radius: 50%; background: var(--brand); color: white; font: 700 10px sans-serif; }
		.hero { position: relative; min-height: 590px; display: flex; align-items: center; overflow: hidden; background: #47351f; color: white; }
		.hero::before { position: absolute; inset: 0; content: ""; background: linear-gradient(90deg, rgba(23,17,11,.78), rgba(23,17,11,.45) 50%, rgba(23,17,11,.08)), url("https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=2000&q=85") center 52%/cover; }
		.hero-content { position: relative; width: min(1320px, 100%); margin: auto; padding: 82px 28px 104px; animation: rise .7s ease both; }
		.eyebrow { display: inline-block; margin-bottom: 18px; color: #ffd5c5; font-size: 11px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; }
		.hero h1 { max-width: 680px; margin: 0; font: 700 72px/.99 Georgia, serif; }
		.hero p { max-width: 480px; margin: 22px 0 28px; color: #f3e9dc; font-size: 16px; line-height: 1.7; }
		.actions { display: flex; flex-wrap: wrap; gap: 11px; }
		.button { min-height: 46px; display: inline-flex; align-items: center; justify-content: center; padding: 0 21px; border: 1px solid transparent; background: var(--brand); color: white; font-size: 13px; font-weight: 700; text-decoration: none; cursor: pointer; transition: background .18s ease, transform .18s ease; }
		.button:hover { background: var(--brand-dark); transform: translateY(-1px); }
		.button-light { border-color: rgba(255,255,255,.7); background: transparent; }
		.button-light:hover { background: white; color: var(--ink); }
		.hero-note { position: absolute; right: max(28px, calc((100vw - 1264px)/2)); bottom: 32px; color: white; font-size: 11px; letter-spacing: 1px; text-transform: uppercase; }
		.content-section { max-width: 1320px; margin: auto; padding: 86px 28px; }
		.section-heading { display: flex; align-items: end; justify-content: space-between; gap: 20px; margin-bottom: 28px; }
		.section-heading h2 { margin: 0; font: 700 38px/1.08 Georgia, serif; }
		.section-heading p { max-width: 440px; margin: 0; color: var(--muted); font-size: 14px; line-height: 1.6; }
		.text-link { color: var(--brand); font-size: 13px; font-weight: 700; text-decoration: none; white-space: nowrap; }
		.category-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
		.category-tile { position: relative; min-height: 300px; display: flex; align-items: end; overflow: hidden; background: #766045; color: white; text-decoration: none; }
		.category-tile::before { position: absolute; inset: 0; content: ""; background: linear-gradient(0deg, rgba(22,16,10,.78), transparent 70%); transition: transform .4s ease; }
		.category-tile:hover::before { transform: scale(1.04); }
		.category-tile img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; transition: transform .45s ease; }
		.category-tile:hover img { transform: scale(1.04); }
		.category-copy { position: relative; padding: 24px; }
		.category-copy span { color: #f5d9c4; font-size: 10px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; }
		.category-copy h3 { margin: 7px 0 0; font: 700 29px Georgia, serif; }
		.packed-band { background: var(--cream); }
		.packed-inner { max-width: 1320px; margin: auto; padding: 70px 28px; display: grid; grid-template-columns: 1.1fr .9fr; align-items: center; gap: 55px; }
		.packed-image { width: 100%; height: 370px; object-fit: cover; }
		.packed-copy h2 { margin: 0 0 14px; font: 700 42px Georgia, serif; }
		.packed-copy p { color: var(--muted); font-size: 15px; line-height: 1.75; }
		.minimum-note { display: inline-flex; align-items: center; gap: 9px; margin: 12px 0 23px; color: var(--green); font-size: 13px; font-weight: 700; }
		.minimum-note::before { content: "✓"; width: 22px; height: 22px; display: grid; place-items: center; border-radius: 50%; background: #dce9df; }
		.catering { background: #24201d; color: white; }
		.catering-inner { max-width: 1320px; margin: auto; padding: 76px 28px; display: grid; grid-template-columns: 1fr 1fr; gap: 55px; align-items: center; }
		.catering h2 { margin: 0 0 16px; font: 700 43px/1.05 Georgia, serif; }
		.catering p { max-width: 520px; color: #ccc2b6; font-size: 15px; line-height: 1.75; }
		.catering-image { width: 100%; height: 330px; object-fit: cover; }
		.inquiry { background: #f6f0e6; }
		.inquiry-inner { max-width: 940px; margin: auto; padding: 78px 28px; }
		.inquiry-inner h2 { margin: 0 0 9px; font: 700 38px Georgia, serif; }
		.inquiry-intro { margin: 0 0 28px; color: var(--muted); line-height: 1.6; }
		.inquiry-form { display: grid; grid-template-columns: 1fr 1fr; gap: 17px; }
		.field { display: grid; gap: 7px; }
		.field-wide { grid-column: 1 / -1; }
		.field label { font-size: 12px; font-weight: 700; }
		.field input, .field select, .field textarea { width: 100%; min-height: 44px; padding: 11px 12px; border: 1px solid #d9d0c5; border-radius: 0; background: white; color: var(--ink); font: inherit; font-size: 14px; }
		.field textarea { min-height: 112px; resize: vertical; }
		.field small { color: var(--muted); font-size: 11px; }
		.form-note { grid-column: 1 / -1; color: var(--muted); font-size: 12px; line-height: 1.5; }
		.site-footer { padding: 28px; background: #191715; color: #d8d0c7; }
		.footer-inner { max-width: 1264px; margin: auto; display: flex; justify-content: space-between; gap: 20px; font-size: 12px; }
		.back-top { position: fixed; right: 20px; bottom: 20px; z-index: 5; width: 42px; height: 42px; display: grid; place-items: center; background: var(--brand); color: white; font-size: 19px; text-decoration: none; }
		@keyframes rise { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
		@media (max-width: 1120px) { .header-inner { flex-wrap: wrap; gap: 14px 24px; } .main-nav { order: 3; flex-basis: 100%; justify-content: flex-start; overflow-x: auto; padding: 7px 0; } .header-tools { margin-left: auto; } }
		@media (max-width: 700px) { .header-inner { padding: 12px 18px; } .search-form { width: 42px; } .search-form input { display: none; } .main-nav { gap: 17px; } .hero { min-height: 570px; } .hero-content { padding: 78px 22px 100px; } .hero h1 { font-size: 52px; } .hero-note { right: 22px; } .content-section { padding: 62px 20px; } .section-heading { display: block; } .section-heading h2 { font-size: 33px; margin-bottom: 10px; } .section-heading p { margin-bottom: 15px; } .category-grid { grid-template-columns: 1fr; gap: 12px; } .category-tile { min-height: 240px; } .packed-inner, .catering-inner { grid-template-columns: 1fr; gap: 28px; padding: 56px 20px; } .packed-image { height: 270px; } .packed-copy h2, .catering h2 { font-size: 36px; } .catering-image { height: 240px; } .inquiry-inner { padding: 58px 20px; } .inquiry-form { grid-template-columns: 1fr; } .field-wide, .form-note { grid-column: auto; } .footer-inner { flex-direction: column; } }
		@media (prefers-reduced-motion: reduce) { *, *::before, *::after { scroll-behavior: auto !important; animation-duration: .01ms !important; transition-duration: .01ms !important; } }
	</style>
</head>
<body id="top">
	<header class="site-header">
		<div class="header-inner">
			<a class="brand" href="<?= base_url('/') ?>" aria-label="Riz Catering home"><span class="brand-mark">R</span><span class="brand-name">Riz Catering<small>Gather around good food</small></span></a>
			<nav class="main-nav" aria-label="Main navigation">
				<a href="<?= base_url('/menu') ?>">Menu</a>
				<details class="nav-group"><summary>Food Trays</summary><div class="nav-dropdown"><a href="<?= base_url('/menu') ?>">Beef &amp; Pork</a><a href="<?= base_url('/menu') ?>">Chicken &amp; Seafood</a><a href="<?= base_url('/menu') ?>">Vegetables &amp; Sides</a></div></details>
				<details class="nav-group"><summary>Packed Meals</summary><div class="nav-dropdown"><a href="<?= base_url('/menu') ?>">Packed Meals</a><a href="<?= base_url('/menu') ?>">Rotation Meals</a></div></details>
				<details class="nav-group"><summary>Packages</summary><div class="nav-dropdown"><a href="<?= base_url('/menu') ?>">Filipino Salu-Salo</a><a href="<?= base_url('/menu') ?>">Celebration Packages</a><a href="<?= base_url('/menu') ?>">Merienda</a></div></details>
				<a href="#catering">Catering</a><a href="#inquiry">Contact</a>
			</nav>
			<div class="header-tools">
				<form class="search-form" action="<?= base_url('/menu') ?>" method="get" role="search"><input name="q" type="search" placeholder="Search food" aria-label="Search food"><button type="submit" aria-label="Search">⌕</button></form>
				<a class="bag-button" href="<?= base_url('/menu') ?>" aria-label="Open menu and order form">🛍<span class="bag-count" aria-label="0 items">0</span></a>
			</div>
		</div>
	</header>

	<main>
		<section class="hero" aria-labelledby="hero-title">
			<div class="hero-content"><span class="eyebrow">Food made for sharing</span><h1 id="hero-title">A little more joy at every table.</h1><p>Filipino favorites and generous spreads, prepared for the moments that bring everyone together.</p><div class="actions"><a class="button" href="<?= base_url('/menu') ?>">Explore the menu</a><a class="button button-light" href="#inquiry">Plan your event</a></div></div>
			<span class="hero-note">Made fresh for your gathering</span>
		</section>

		<section class="content-section" id="menu">
			<div class="section-heading"><div><span class="eyebrow" style="color:var(--brand)">Find your spread</span><h2>What are we gathering for?</h2></div><div><p>From a few favorites for the table to a full celebration, start with the kind of meal you have in mind.</p><a class="text-link" href="<?= base_url('/menu') ?>">Browse all dishes &rarr;</a></div></div>
			<div class="category-grid">
				<a class="category-tile" href="<?= base_url('/menu') ?>"><img src="https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=1000&q=80" alt="A colorful shared meal with fresh ingredients"><span class="category-copy"><span>For the whole table</span><h3>Food Trays</h3></span></a>
				<a class="category-tile" href="<?= base_url('/menu') ?>"><img src="https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=1000&q=80" alt="A prepared meal arranged for one"><span class="category-copy"><span>Easy to serve</span><h3>Packed Meals</h3></span></a>
				<a class="category-tile" href="<?= base_url('/menu') ?>"><img src="https://images.unsplash.com/photo-1555939594-58d7cb561ad1?auto=format&fit=crop&w=1000&q=80" alt="A generous barbecue spread ready to share"><span class="category-copy"><span>Made for milestones</span><h3>Party Packages</h3></span></a>
			</div>
		</section>

		<section class="packed-band" id="packed-meals"><div class="packed-inner"><img class="packed-image" src="/riz_catering/assets/riz_catering_pic1.jpg?v=20260926-2" alt="Riz Catering food prepared for a group"><div class="packed-copy"><span class="eyebrow" style="color:var(--brand)">Meals, made simple</span><h2>Good food, ready to go.</h2><p>Thoughtful packed meals make office lunches, team days, and gatherings easier to plan. Browse the menu to find the right dishes for your group.</p><span class="minimum-note">Packed meal orders start at 5 PAX</span><br><a class="button" href="<?= base_url('/menu') ?>">See packed meal options</a></div></div></section>

		<section class="catering" id="catering"><div class="catering-inner"><div><span class="eyebrow">Your event, thoughtfully catered</span><h2>For all your catering needs.</h2><p>Tell us about the occasion, your guest count, and the kind of food you love. We can help shape a spread that feels right for your day.</p><div class="actions"><a class="button" href="#inquiry">Start a catering inquiry</a><a class="button button-light" href="<?= base_url('/menu') ?>">View the menu</a></div></div><img class="catering-image" src="https://images.unsplash.com/photo-1511795409834-ef04bbd61622?auto=format&fit=crop&w=1200&q=80" alt="A warmly set table prepared for a celebration"></div></section>

		<section class="inquiry" id="inquiry"><div class="inquiry-inner"><span class="eyebrow" style="color:var(--brand)">Let's plan together</span><h2>Catering inquiry</h2><p class="inquiry-intro">Share a few event details to get started. This form is a design preview and is not connected to inquiry submission yet; please use the Menu page for the current booking request flow.</p><div class="inquiry-form">
				<div class="field"><label for="inquiry-name">Full name *</label><input id="inquiry-name" name="name" autocomplete="name" required placeholder="Your name"></div>
				<div class="field"><label for="inquiry-mobile">Mobile number *</label><input id="inquiry-mobile" name="mobile" type="tel" autocomplete="tel" required placeholder="09XX XXX XXXX"></div>
				<div class="field"><label for="inquiry-email">Email address *</label><input id="inquiry-email" name="email" type="email" autocomplete="email" required placeholder="you@example.com"></div>
				<div class="field"><label for="inquiry-guests">Number of guests *</label><input id="inquiry-guests" name="guests" type="number" min="50" required placeholder="Minimum 50 guests"><small>Minimum catering order: 50 guests</small></div>
				<div class="field"><label for="inquiry-date">Date of event *</label><input id="inquiry-date" name="date" type="date" required></div>
				<div class="field"><label for="inquiry-occasion">Occasion *</label><select id="inquiry-occasion" name="occasion" required><option value="" disabled selected>Select an occasion</option><option>Birthday</option><option>Wedding</option><option>Corporate event</option><option>Family gathering</option><option>Other</option></select></div>
				<div class="field"><label for="inquiry-start">Start time</label><input id="inquiry-start" name="start_time" type="time"></div>
				<div class="field"><label for="inquiry-end">End time</label><input id="inquiry-end" name="end_time" type="time"></div>
				<div class="field field-wide"><label for="inquiry-venue">Venue *</label><input id="inquiry-venue" name="venue" required placeholder="Event venue and address"></div>
				<div class="field"><label for="inquiry-budget">Estimated budget</label><input id="inquiry-budget" name="budget" type="number" min="0" placeholder="PHP"></div>
				<div class="field"><label for="inquiry-motif">Motif or theme</label><input id="inquiry-motif" name="motif" placeholder="Colors or theme"></div>
				<div class="field field-wide"><label for="inquiry-requests">Special requests</label><textarea id="inquiry-requests" name="requests" placeholder="Dietary needs, setup details, or anything else we should know"></textarea></div>
				<p class="form-note">This preview does not send or store your information. Use the Menu page to submit a booking request through the currently available flow.</p>
				<div class="field-wide"><a class="button" href="<?= base_url('/menu') ?>">Continue to booking request</a></div>
			</div></div></section>
	</main>
	<footer class="site-footer"><div class="footer-inner"><span>Riz Catering</span><span>Good food, shared generously.</span><a href="#top">Back to top &uarr;</a></div></footer>
	<a class="back-top" href="#top" aria-label="Back to top">↑</a>
</body>
</html>
