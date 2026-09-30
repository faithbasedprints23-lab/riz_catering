<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Riz Catering - Owner Operations Hub</title>
  <style>
    :root { --bg: #0f172a; --card: #1e293b; --accent: #f59e0b; --emerald: #10b981; --text: #f8fafc; --muted: #94a3b8; --border: #334155; }
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: system-ui, sans-serif; }
    body { background: var(--bg); color: var(--text); padding: 24px; }
    nav { display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto 32px; padding: 16px; background: var(--card); border-radius: 12px; border: 1px solid var(--border); }
    nav a { color: var(--text); text-decoration: none; margin-left: 16px; }
    nav a.active { color: var(--accent); }
    .container { max-width: 1200px; margin: 0 auto; }
    .metrics { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
    .metric-card { background: var(--card); padding: 20px; border-radius: 12px; border: 1px solid var(--border); }
    .metric-card h3 { font-size: 1.8rem; color: var(--emerald); margin-top: 4px; }
    .layout-grid { display: grid; grid-template-columns: 1.8fr 1.2fr; gap: 24px; }
    @media(max-width: 900px) { .layout-grid { grid-template-columns: 1fr; } }
    .panel { background: var(--card); padding: 20px; border-radius: 12px; border: 1px solid var(--border); margin-bottom: 24px; }
    table { width: 100%; border-collapse: collapse; margin-top: 12px; }
    th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border); font-size: 0.9rem; }
    th { color: var(--muted); }
    .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; }
    .badge-confirmed { background: rgba(16,185,129,0.2); color: var(--emerald); }
    .badge-pending { background: rgba(245,158,11,0.2); color: var(--accent); }
    .badge-pending-payment { background: rgba(245,158,11,0.2); color: var(--accent); }
    .input-field { width: 100%; padding: 10px; margin-top: 6px; margin-bottom: 12px; background: #0f172a; border: 1px solid var(--border); color: white; border-radius: 6px; }
    .btn { background: var(--accent); color: #000; border: none; padding: 10px 16px; border-radius: 6px; font-weight: bold; cursor: pointer; }
  </style>
</head>
<body>
  <nav>
    <h2 style="color:var(--accent);">Riz Catering - Owner Control Center</h2>
    <div>
      <a href="<?= base_url('/') ?>">Event Builder</a>
      <a href="<?= base_url('/menu') ?>">Menu Catalog</a>
      <a href="<?= base_url('/ownerdashboard') ?>" class="active">Owner Portal</a>
    </div>
  </nav>

  <div class="container">
    <div class="metrics">
      <div class="metric-card">
        <span style="color:var(--muted);">Verified Revenue</span>
        <h3>&#8369;<?= number_format($total_revenue, 2) ?></h3>
      </div>
      <div class="metric-card">
        <span style="color:var(--muted);">Active Events</span>
        <h3 style="color:var(--accent);"><?= $upcoming_events ?></h3>
      </div>
    </div>

    <?php if ($success = session()->getFlashdata('success')): ?><p role="status" style="margin-bottom:16px; color:#6ee7b7;"> <?= esc($success) ?></p><?php endif; ?>
    <?php if ($error = session()->getFlashdata('error')): ?><p role="alert" style="margin-bottom:16px; color:#fca5a5;"> <?= esc($error) ?></p><?php endif; ?>

    <div class="layout-grid">
      <!-- Left Panel: Bookings Table -->
      <div class="panel">
        <h3>Master Bookings Schedule</h3>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Client</th>
              <th>Event</th>
              <th>Total</th>
              <th>Deposit due</th>
              <th>Paid</th>
              <th>Balance</th>
              <th>Payment</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($orders)): ?>
              <?php foreach ($orders as $order): ?>
                <tr>
                  <td><strong>#RZ-<?= $order['id'] ?></strong></td>
                  <td><?= esc($order['client_name']) ?></td>
                  <td><?= esc($order['event_date']) ?><br><small><?= esc(substr((string) ($order['event_start_time'] ?? ''), 0, 5)) ?>–<?= esc(substr((string) ($order['event_end_time'] ?? ''), 0, 5)) ?></small></td>
                  <td>&#8369;<?= number_format($order['total_price'], 2) ?></td>
                  <td>&#8369;<?= number_format($order['down_payment_due'] ?? 0, 2) ?></td>
                  <td>&#8369;<?= number_format($order['amount_paid'] ?? 0, 2) ?></td>
                  <td>&#8369;<?= number_format($order['balance_due'] ?? $order['total_price'], 2) ?></td>
                  <td><strong style="color:var(--accent);"> <?= esc($order['payment_method'] ?? 'Cash') ?></strong><br><small><?= esc($order['payment_status'] ?? 'Pending') ?></small></td>
                  <?php $statusClass = strtolower(str_replace(' ', '-', $order['status'])); ?>
                  <td><span class="badge badge-<?= esc($statusClass, 'attr') ?>"><?= esc($order['status']) ?></span></td>
                  <td>
                    <?php if ($order['status'] === 'Pending'): ?>
                      <form action="<?= base_url('/ownerdashboard/updateStatus/' . $order['id']) ?>" method="POST" style="display:inline-block;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="status" value="Confirmed">
                        <button type="submit" class="btn" style="font-size:0.75rem; background:var(--emerald); color:white;">Confirm (50% deposit)</button>
                      </form>
                    <?php elseif ($order['status'] === 'Confirmed'): ?>
                      <form action="<?= base_url('/ownerdashboard/updateStatus/' . $order['id']) ?>" method="POST" style="display:inline-block;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="status" value="Processing">
                        <button type="submit" class="btn" style="font-size:0.75rem; background:var(--accent);">Mark preparing</button>
                      </form>
                    <?php elseif ($order['status'] === 'Processing'): ?>
                      <form action="<?= base_url('/ownerdashboard/updateStatus/' . $order['id']) ?>" method="POST" style="display:inline-block;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="status" value="Completed">
                        <button type="submit" class="btn" style="font-size:0.75rem; background:var(--emerald); color:white;">Mark completed</button>
                      </form>
                    <?php endif; ?>
                    <?php if ($order['status'] !== 'Cancelled' && (float) ($order['balance_due'] ?? $order['total_price']) > 0): ?>
                      <form action="<?= base_url('/ownerdashboard/recordPayment/' . $order['id']) ?>" method="POST" style="min-width:170px; margin-top:6px;">
                        <?= csrf_field() ?>
                        <input type="number" name="amount" min="0.01" max="<?= esc((string) ($order['balance_due'] ?? $order['total_price']), 'attr') ?>" step="0.01" placeholder="Amount (PHP)" required style="width:100%; margin:3px 0;">
                        <select name="payment_method" required style="width:100%; margin:3px 0;">
                          <option value="Cash">Cash</option><option value="GCash">GCash</option><option value="Bank Transfer">Bank Transfer</option><option value="Check">Check</option>
                        </select>
                        <input type="text" name="reference_number" maxlength="120" placeholder="Reference / receipt" style="width:100%; margin:3px 0;">
                        <button type="submit" class="btn" style="width:100%; font-size:0.75rem;">Record payment</button>
                      </form>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="10" style="color:var(--muted);">No reservations found in database.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Right Panel: Add Menu Item Form -->
      <div class="panel">
        <h3>Add New Menu Item</h3>
        <p style="color:var(--muted); font-size: 0.85rem; margin-top: 4px;">Insert dishes directly into MySQL</p>

        <form action="<?= base_url('/ownerdashboard/addMenuItem') ?>" method="POST" style="margin-top: 16px;">
          <label>Dish Name</label>
          <input type="text" name="name" class="input-field" required placeholder="e.g., Truffle Herb Chicken">

          <label>Description</label>
          <textarea name="description" class="input-field" placeholder="Ingredients and details"></textarea>

          <label>Price (PHP)</label>
          <input type="number" step="0.01" name="price" class="input-field" required placeholder="25.00">

          <label>Category</label>
          <select name="category" class="input-field">
            <option value="Entree">Entree</option>
            <option value="Side">Side</option>
            <option value="Dessert">Dessert</option>
          </select>

          <div style="margin-bottom: 16px;">
            <label><input type="checkbox" name="is_vegan" value="1"> Vegan</label> &nbsp;
            <label><input type="checkbox" name="is_gluten_free" value="1"> Gluten-Free</label>
          </div>

          <button type="submit" class="btn" style="width: 100%;">Save to Menu</button>
        </form>
      </div>
    </div>
  </div>
</body>
</html>