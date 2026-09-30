<?php
$pageTitle = 'Food packages';
$packageChoices = [];
foreach ($packageItems as $link) $packageChoices[(int) $link['package_id']][(int) $link['menu_item_id']] = (string) $link['additional_charge'];
$activeItems = array_values(array_filter($items, static fn ($item) => (int) $item['is_active'] === 1));
?>
<section class="owner-module">
    <header class="module-heading">
        <div><span class="eyebrow section-eyebrow">Catering offers</span><h1>Food packages</h1><p>Group dishes into sets, add a package photo and description, and set a fixed or per-person price.</p></div>
        <span class="module-total"><?= count($packages) ?> packages</span>
    </header>
    <nav class="menu-admin-tabs" aria-label="Menu sections"><a href="<?= rc_url('menu-admin') ?>">Individual food menu</a><a class="active" aria-current="page" href="<?= rc_url('packages-admin') ?>">Food packages</a></nav>

    <section class="surface">
        <div class="surface-heading"><div><h2>Food package sets</h2><span>Historical reservations keep their saved totals</span></div><button class="button" type="button" data-modal-open="create-package-modal">Create package</button></div>
        <?php if ($packages): ?><div class="package-admin-list">
            <?php foreach ($packages as $package): $packageId = (int) $package['package_id']; ?>
                <article class="package-admin-entry">
                    <div class="package-admin-summary"><span><strong><?= rc_e($package['package_name']) ?></strong><small><?= rc_money(rc_cents($package['package_price'])) ?> <?= $package['price_type'] === 'Per Person' ? '/ person' : 'fixed' ?> · <?= (int) $package['minimum_guests'] ?>–<?= $package['maximum_guests'] ? (int) $package['maximum_guests'] : '∞' ?> guests · <?= rc_e($package['status']) ?></small></span><div class="package-admin-actions"><button class="button button-secondary" type="button" data-modal-open="edit-package-<?= $packageId ?>">Edit package</button><form method="post" action="<?= rc_e(rc_endpoint()) ?>" data-confirm="Change availability for <?= rc_e($package['package_name']) ?>? Existing reservation records will remain unchanged."><?= rc_csrf_field() ?><input type="hidden" name="action" value="toggle-package"><input type="hidden" name="id" value="<?= $packageId ?>"><button class="text-link danger-link" type="submit"><?= $package['status'] === 'Active' ? 'Archive' : 'Restore' ?></button></form></div></div>
                </article>
                <dialog class="package-edit-modal" id="edit-package-<?= $packageId ?>" aria-labelledby="edit-package-heading-<?= $packageId ?>">
                    <div class="package-modal-heading"><div><span class="eyebrow section-eyebrow">Food package</span><h2 id="edit-package-heading-<?= $packageId ?>">Edit <?= rc_e($package['package_name']) ?></h2><p>Update the package details, included dishes, photo, and pricing.</p></div><button class="package-modal-close" type="button" data-modal-close aria-label="Close dialog">×</button></div>
                    <form method="post" enctype="multipart/form-data" action="<?= rc_e(rc_endpoint()) ?>" class="form-stack package-modal-form">
                        <?= rc_csrf_field() ?><input type="hidden" name="action" value="update-package"><input type="hidden" name="id" value="<?= $packageId ?>">
                        <label>Package name<input name="package_name" maxlength="160" value="<?= rc_e($package['package_name']) ?>" required></label>
                        <label>Description<textarea name="description" maxlength="5000"><?= rc_e($package['description']) ?></textarea></label>
                        <?php if (!empty($package['image_url'])): ?><img class="menu-admin-preview" src="<?= rc_e(rc_asset_url((string) $package['image_url'])) ?>" alt="Current photo of <?= rc_e($package['package_name']) ?>"><?php endif; ?>
                        <label>Choose a photo<input name="image_file" type="file" accept="image/jpeg,image/png,image/webp"></label>
                        <label>Or image URL<input name="image_url" maxlength="1000" value="<?= preg_match('~^https?://~i', (string) $package['image_url']) ? rc_e($package['image_url']) : '' ?>" placeholder="Keep current photo or add a URL"></label>
                        <?php if (!empty($package['image_url'])): ?><label class="package-remove-image"><input type="checkbox" name="remove_image" value="1"> Remove current photo</label><?php endif; ?>
                        <div class="package-modal-grid">
                            <label>Offering type<select name="package_type"><option value="custom" <?= ($package['package_type']??'custom')==='custom'?'selected':'' ?>>Custom set</option><option value="packed_meal" <?= ($package['package_type']??'')==='packed_meal'?'selected':'' ?>>Packed meal tier</option><option value="buffet" <?= ($package['package_type']??'')==='buffet'?'selected':'' ?>>Buffet set</option></select></label><label>Set / tier name<input name="set_name" maxlength="80" value="<?= rc_e((string)($package['set_name']??'')) ?>" placeholder="Tier A or Set A"></label>
                            <label>Pricing model<select name="price_type"><option <?= $package['price_type'] === 'Fixed' ? 'selected' : '' ?>>Fixed</option><option <?= $package['price_type'] === 'Per Person' ? 'selected' : '' ?>>Per Person</option></select></label>
                            <label>Price (PHP)<input name="package_price" type="number" min="0.01" step="0.01" value="<?= rc_e($package['package_price']) ?>" required></label>
                            <label>Minimum guests<input name="minimum_guests" type="number" min="1" value="<?= (int) $package['minimum_guests'] ?>" required></label>
                            <label>Maximum guests (optional)<input name="maximum_guests" type="number" min="1" value="<?= rc_e((string) $package['maximum_guests']) ?>"></label>
                        </div>
                        <fieldset class="package-modal-dishes"><legend>Included dishes and price adjustments</legend>
                            <?php if ($activeItems): ?><div class="package-choice-list"><?php foreach ($activeItems as $item): $itemId = (int) $item['id']; ?>
                                <div class="package-choice-row"><label class="option-choice"><input type="checkbox" name="menu_items[]" value="<?= $itemId ?>" <?= array_key_exists($itemId, $packageChoices[$packageId] ?? []) ? 'checked' : '' ?>><span><strong><?= rc_e($item['name']) ?></strong><small><?= rc_e($item['category']) ?> · <?= rc_money(rc_cents($item['price'])) ?></small></span></label><label class="option-charge">Extra charge (PHP)<input name="option_charges[<?= $itemId ?>]" type="number" min="0" step="0.01" value="<?= rc_e($packageChoices[$packageId][$itemId] ?? '0.00') ?>"></label></div>
                            <?php endforeach; ?></div><?php else: ?><p class="muted">Add active dishes before assigning them to a package.</p><?php endif; ?>
                        </fieldset>
                        <div class="package-modal-footer"><button class="button button-secondary" type="button" data-modal-close>Cancel</button><button class="button" type="submit">Save package</button></div>
                    </form>
                </dialog>
            <?php endforeach; ?>
        </div><?php else: ?><div class="empty-state"><strong>No packages yet</strong>Create a package after adding its dishes.</div><?php endif; ?>
    </section>
</section>

<dialog class="package-edit-modal" id="create-package-modal" aria-labelledby="create-package-heading">
    <div class="package-modal-heading"><div><span class="eyebrow section-eyebrow">Catering offers</span><h2 id="create-package-heading">Create a food package</h2><p>Choose its included dishes and price.</p></div><button class="package-modal-close" type="button" data-modal-close aria-label="Close dialog">×</button></div>
    <form method="post" enctype="multipart/form-data" action="<?= rc_e(rc_endpoint()) ?>" class="form-stack package-modal-form">
        <?= rc_csrf_field() ?><input type="hidden" name="action" value="package">
        <label>Package name<input name="package_name" maxlength="160" required></label>
        <label>Description<textarea name="description" maxlength="5000"></textarea></label>
        <label>Package photo (optional)<input name="image_file" type="file" accept="image/jpeg,image/png,image/webp"></label>
        <label>Or image URL<input name="image_url" maxlength="1000" placeholder="https://…"></label>
        <label>Offering type<select name="package_type"><option value="custom">Custom set</option><option value="packed_meal">Packed meal tier</option><option value="buffet">Buffet set</option></select></label><label>Set / tier name<input name="set_name" maxlength="80" placeholder="Tier A or Set A"></label>
        <div class="package-modal-grid"><label>Pricing model<select name="price_type"><option>Fixed</option><option selected>Per Person</option></select></label><label>Price (PHP)<input name="package_price" type="number" min="0.01" step="0.01" required></label><label>Minimum guests<input name="minimum_guests" type="number" min="1" value="50" required></label><label>Maximum guests (optional)<input name="maximum_guests" type="number" min="1"></label></div>
        <fieldset class="package-modal-dishes"><legend>Included dishes · select every dish in this set</legend>
            <?php if ($activeItems): ?><div class="package-choice-list"><?php foreach ($activeItems as $item): $itemId = (int) $item['id']; ?>
                <div class="package-choice-row"><label class="option-choice"><input type="checkbox" name="menu_items[]" value="<?= $itemId ?>"><span><strong><?= rc_e($item['name']) ?></strong><small><?= rc_e($item['category']) ?> · <?= rc_money(rc_cents($item['price'])) ?></small></span></label><label class="option-charge">Extra charge (PHP)<input name="option_charges[<?= $itemId ?>]" type="number" min="0" step="0.01" value="0.00"></label></div>
            <?php endforeach; ?></div><?php else: ?><p class="muted">Add active dishes from Individual food menu before creating a package.</p><?php endif; ?>
        </fieldset>
        <div class="package-modal-footer"><button class="button button-secondary" type="button" data-modal-close>Cancel</button><button class="button" type="submit" <?= $activeItems ? '' : 'disabled' ?>>Create package</button></div>
    </form>
</dialog>
