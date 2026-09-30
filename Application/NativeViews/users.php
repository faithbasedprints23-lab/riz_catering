<?php $pageTitle = 'Customer accounts'; ?>
<section class="owner-module">
  <header class="module-heading"><div><span class="eyebrow section-eyebrow">Customer access</span><h1>Customer accounts</h1><p>Manage customer sign-in access or reset a customer password. The owner account is managed from its own profile.</p></div><span class="module-total"><?= count($users) ?> accounts</span></header>
  <section class="surface sales-section"><div class="surface-heading"><h2>Customer accounts</h2><span>Owner access is kept separate</span></div>
    <?php if ($users): ?><div class="table-wrap"><table><thead><tr><th>Customer</th><th>Joined</th><th>Access</th><th>Account action</th></tr></thead><tbody>
      <?php foreach ($users as $entry): $userId = (int) $entry['user_id']; ?>
        <tr><td><?= rc_e($entry['full_name']) ?><small><?= rc_e($entry['email']) ?> · #<?= $userId ?></small></td><td><?= rc_e($entry['created_at']) ?></td><td><span class="status <?= $entry['status'] === 'Active' ? 'status-completed' : 'status-cancelled' ?>"><?= rc_e($entry['status']) ?></span></td>
          <td><div class="customer-account-actions"><form method="post" action="<?= rc_e(rc_endpoint()) ?>" data-confirm="<?= $entry['status'] === 'Active' ? 'Disable' : 'Enable' ?> customer access for <?= rc_e($entry['email']) ?>?"><?= rc_csrf_field() ?><input type="hidden" name="action" value="customer-status"><input type="hidden" name="user_id" value="<?= $userId ?>"><input type="hidden" name="status" value="<?= $entry['status'] === 'Active' ? 'Disabled' : 'Active' ?>"><button class="button button-secondary" type="submit"><?= $entry['status'] === 'Active' ? 'Disable account' : 'Enable account' ?></button></form>
            <details class="password-reset-details"><summary>Reset password</summary><form method="post" action="<?= rc_e(rc_endpoint()) ?>" data-confirm="Reset this customer password? Share the temporary password privately."><?= rc_csrf_field() ?><input type="hidden" name="action" value="reset-user-password"><input type="hidden" name="user_id" value="<?= $userId ?>"><label>Temporary password<input class="input-field" name="new_password" type="password" minlength="14" autocomplete="new-password" required></label><button class="button button-secondary" type="submit">Reset password</button></form></details></div></td>
        </tr>
      <?php endforeach; ?></tbody></table></div><?php else: ?><div class="empty-state"><strong>No customer accounts found</strong>Customer registrations will appear here.</div><?php endif; ?>
  </section>
</section>
