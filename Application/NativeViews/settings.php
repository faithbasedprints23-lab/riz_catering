<?php $pageTitle = 'Settings'; ?>
<section class="owner-module">
    <header class="module-heading"><div><span class="eyebrow section-eyebrow">Owner configuration</span><h1>Business settings</h1><p>Maintain business contact details, operational notes, customer records, and administrative access.</p></div></header>
    <div class="settings-grid">
        <section class="surface">
            <div class="surface-heading"><h2>Owner and business information</h2><span>Saved centrally</span></div>
            <form class="settings-form owner-profile-form" method="post" action="<?= rc_e(rc_endpoint()) ?>">
                <?= rc_csrf_field() ?><input type="hidden" name="action" value="owner-profile">
                <h3>Owner account profile</h3>
                <div class="field"><label for="owner-full-name">Name</label><input class="input-field" id="owner-full-name" name="full_name" maxlength="120" required value="<?= rc_e($user['full_name']) ?>"></div>
                <div class="field"><label for="owner-email">Email</label><input class="input-field" id="owner-email" name="email" type="email" maxlength="254" required value="<?= rc_e($user['email']) ?>"></div>
                <button class="button button-secondary" type="submit">Save owner profile</button>
            </form>
            <form class="settings-form" method="post" action="<?= rc_e(rc_endpoint()) ?>">
                <?= rc_csrf_field() ?><input type="hidden" name="action" value="save-settings">
                <div class="field"><label for="business-name">Business name</label><input class="input-field" id="business-name" name="business_name" required maxlength="160" value="<?= rc_e($settings['business_name'] ?? 'Riz Catering Services') ?>"></div>
                <div class="field"><label for="business-email">Business email</label><input class="input-field" id="business-email" name="business_email" type="email" maxlength="254" value="<?= rc_e($settings['business_email'] ?? '') ?>"></div>
                <div class="field"><label for="business-phone">Business phone</label><input class="input-field" id="business-phone" name="business_phone" type="tel" maxlength="40" value="<?= rc_e($settings['business_phone'] ?? '') ?>"></div>
                <div class="field"><label for="business-address">Business address</label><textarea class="input-field" id="business-address" name="business_address" maxlength="500"><?= rc_e($settings['business_address'] ?? '') ?></textarea></div>
                <div class="policy-accept"><p><strong>Cancellation policy (system rule):</strong> the 50% deposit is non-refundable and includes the 10% cancellation fee. Amounts already paid above the deposit are shown as refund due and must be refunded manually outside this system.</p></div>
                <button class="button" type="submit">Save business settings</button>
            </form>
            <div class="surface-heading settings-subheading"><h2>Payment rules in effect</h2><span>System policy</span></div>
            <ul class="settings-policy-list"><li><strong>Deposit:</strong> 50% is required before reservation confirmation and is non-refundable on cancellation.</li><li><strong>Cancellation fee:</strong> the 10% of order total is included within the 50% deposit, not added on top. Any amount paid above the deposit is a refund due, paid manually.</li><li><strong>Completion:</strong> full payment is required before a reservation can be marked completed.</li><li><strong>Offline methods:</strong> Cash, GCash, Bank Transfer, and Check.</li><li>The owner records payments for audit purposes; this application does not process gateway transactions.</li></ul>
        </section>
        <section class="surface">
            <div class="surface-heading"><h2>Owner tools</h2><span>Administrative access</span></div>
            <div class="settings-link-list">
                <a href="<?= rc_url('customers') ?>"><span><strong>Customer directory</strong><small>Find customer details and view reservation history</small></span><b>→</b></a>
                <a href="<?= rc_url('users') ?>"><span><strong>Customer accounts</strong><small>Manage customer access and reset customer passwords</small></span><b>→</b></a>
                <a href="<?= rc_url('notifications') ?>"><span><strong>Notifications</strong><small>Review and clear owner activity alerts</small></span><b>→</b></a>
                <a href="<?= rc_url('audit') ?>"><span><strong>Audit activity</strong><small>Review important administrative changes</small></span><b>→</b></a>
                <a href="<?= rc_url('owner-password') ?>"><span><strong>Change password</strong><small>Update your owner account credentials</small></span><b>→</b></a>
            </div>
        </section>
    </div>
</section>
