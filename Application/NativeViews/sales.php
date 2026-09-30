<?php
$pageTitle = 'Sales';
$exportParams = array_filter($salesFilters, static fn ($value) => $value !== '' && $value !== null);
$exportParams['export'] = 'csv';
?>
<section class="owner-module">
    <header class="module-heading"><div><span class="eyebrow section-eyebrow">Financial reporting</span><h1>Sales and transactions</h1><p>Review reservation totals and manually recorded payments from the system's transaction records.</p></div></header>
    <form class="sales-filter-form" method="get" action="<?= rc_e(rc_endpoint()) ?>">
        <input type="hidden" name="page" value="sales">
        <label>Event date from<input type="date" name="from" value="<?= rc_e($salesFilters['from']) ?>"></label>
        <label>Event date to<input type="date" name="to" value="<?= rc_e($salesFilters['to']) ?>"></label>
        <label>Reservation status<select name="status"><option value="">All statuses</option><?php foreach (['Pending','Confirmed','Processing','Completed','Cancelled','Rejected'] as $status): ?><option value="<?= rc_e($status) ?>" <?= $salesFilters['status'] === $status ? 'selected' : '' ?>><?= rc_e($status) ?></option><?php endforeach; ?></select></label>
        <label>Payment method<select name="method"><option value="">All methods</option><?php foreach (['Cash','GCash','Bank Transfer','Check'] as $method): ?><option value="<?= rc_e($method) ?>" <?= $salesFilters['method'] === $method ? 'selected' : '' ?>><?= rc_e($method) ?></option><?php endforeach; ?></select></label>
        <label>Package<select name="package"><option value="">All packages</option><?php foreach ($salesPackages as $package): ?><option value="<?= (int) $package['package_id'] ?>" <?= (int) $salesFilters['package'] === (int) $package['package_id'] ? 'selected' : '' ?>><?= rc_e($package['package_name']) ?></option><?php endforeach; ?></select></label>
        <label>Search<input type="search" name="q" value="<?= rc_e($salesFilters['q']) ?>" placeholder="Customer, phone, reservation"></label>
        <div class="sales-actions"><button class="button" type="submit">Apply filters</button><a class="button button-secondary" href="<?= rc_url('sales') ?>">Clear</a></div>
    </form>
    <div class="sales-actions"><a class="button button-secondary" href="<?= rc_url('sales', $exportParams) ?>">Export CSV</a><button class="button button-secondary" type="button" onclick="window.print()">Print report</button></div>
    <div class="metrics-grid">
        <article class="metric"><span>Reservations in view</span><strong><?= (int) $salesSummary['order_count'] ?></strong></article>
        <article class="metric"><span>Booked sales</span><strong><?= rc_money(rc_cents($salesSummary['booked_total'])) ?></strong></article>
        <article class="metric"><span>Payments collected</span><strong><?= rc_money(rc_cents($paymentSummary['collected_total'])) ?></strong><small><?= (int) $paymentSummary['transaction_count'] ?> matching transactions</small></article>
        <article class="metric"><span>Outstanding balance</span><strong><?= rc_money(rc_cents($salesSummary['outstanding_total'])) ?></strong></article>
    </div>
    <section class="surface sales-section"><div class="surface-heading"><h2>Reservation sales</h2><span><?= count($salesOrders) ?> matching records</span></div>
        <?php if ($salesOrders): ?><div class="table-wrap"><table><thead><tr><th>Reservation</th><th>Customer</th><th>Package</th><th>Event date</th><th>Status</th><th>Payment</th><th>Total</th><th>Deposit</th><th>Paid</th><th>Balance</th><th>Created</th></tr></thead><tbody><?php foreach ($salesOrders as $order): ?><tr>
            <td><a class="text-link" href="<?= rc_url('orders', ['q' => (int) $order['id']]) ?>">#RZ-<?= (int) $order['id'] ?></a></td><td><?= rc_e($order['client_name']) ?><small><?= rc_e($order['client_mobile']) ?></small></td><td><?= rc_e($order['package_name'] ?: 'Catering order') ?></td><td><?= rc_e($order['event_date']) ?></td><td><?= rc_e($order['status']) ?></td><td><?= rc_e($order['payment_status']) ?><small><?= rc_e($order['payment_method']) ?></small></td><td><?= rc_money(rc_cents($order['total_price'])) ?></td><td><?= rc_money(rc_cents($order['down_payment_due'])) ?></td><td><?= rc_money(rc_cents($order['amount_paid'])) ?></td><td><?= rc_money(rc_cents($order['balance_due'])) ?></td><td><?= rc_e($order['created_at']) ?></td>
        </tr><?php endforeach; ?></tbody></table></div><?php else: ?><div class="empty-state"><strong>No reservations match these filters</strong>Adjust the date range or search terms to see sales records.</div><?php endif; ?>
    </section>
    <section class="surface sales-section"><div class="surface-heading"><h2>Payment history</h2><span>Latest <?= count($salesPayments) ?> matching recorded transactions (up to 250)</span></div>
        <?php if ($salesPayments): ?><div class="table-wrap"><table><thead><tr><th>Payment date</th><th>Reservation</th><th>Customer</th><th>Package</th><th>Method</th><th>Reference</th><th>Amount</th></tr></thead><tbody><?php foreach ($salesPayments as $payment): ?><tr><td><?= rc_e($payment['payment_date']) ?></td><td><a class="text-link" href="<?= rc_url('orders', ['q' => (int) $payment['order_id']]) ?>">#RZ-<?= (int) $payment['order_id'] ?></a></td><td><?= rc_e($payment['client_name']) ?></td><td><?= rc_e($payment['package_name'] ?: 'Catering order') ?></td><td><?= rc_e($payment['payment_method']) ?></td><td><?= rc_e($payment['reference_number'] ?: '—') ?></td><td><?= rc_money(rc_cents($payment['amount'])) ?></td></tr><?php endforeach; ?></tbody></table></div><?php else: ?><div class="empty-state"><strong>No payments found</strong>Recorded offline payments matching your filters will appear here.</div><?php endif; ?>
    </section>
    <p class="report-note">This operational report reflects stored reservation and payment records. It is not a tax or accounting statement. CSV export includes the filtered reservation table.</p>
</section>
