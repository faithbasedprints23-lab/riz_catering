<?php

namespace App\Controllers;
use App\Models\OrderModel;
use App\Models\MenuModel;

class OwnerDashboard extends BaseController
{
    public function index()
    {
        if (!$this->hasOwnerAccess()) {
            return service('response')->setStatusCode(403)->setBody('Owner access is required.');
        }

        $orderModel = new OrderModel();
        $menuModel  = new MenuModel();

        $data['orders']           = $orderModel->orderBy('created_at', 'DESC')->findAll();
        $data['menu_items']       = $menuModel->findAll();
        $data['total_revenue']    = $orderModel->getRevenueTotal();
        $data['upcoming_events']  = $orderModel->getUpcomingEventsCount();

        return view('owner_dashboard', $data);
    }

    // Process new menu submission from owner
    public function addMenuItem()
    {
        if (!$this->hasOwnerAccess()) {
            return service('response')->setStatusCode(403)->setBody('Owner access is required.');
        }

        $menuModel = new MenuModel();

        $saveData = [
            'name'           => $this->request->getPost('name'),
            'description'    => $this->request->getPost('description'),
            'price'          => $this->request->getPost('price'),
            'category'       => $this->request->getPost('category'),
            'is_vegan'       => $this->request->getPost('is_vegan') ? 1 : 0,
            'is_gluten_free' => $this->request->getPost('is_gluten_free') ? 1 : 0,
        ];

        $menuModel->insert($saveData);
        return redirect()->to('/ownerdashboard')->with('success', 'Menu Item Added Successfully');
    }

    public function recordPayment($id)
    {
        if (!$this->hasOwnerAccess()) {
            return service('response')->setStatusCode(403)->setBody('Owner access is required.');
        }

        $orderModel = new OrderModel();
        $order = $orderModel->find($id);
        if (!$order || $order['status'] === 'Cancelled') {
            return redirect()->back()->with('error', 'Payment cannot be recorded for this reservation.');
        }

        $amountInput = trim((string) $this->request->getPost('amount'));
        if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $amountInput)) {
            return redirect()->back()->with('error', 'Enter a valid positive payment amount in PHP.');
        }
        [$whole, $fraction] = array_pad(explode('.', $amountInput, 2), 2, '0');
        $paymentCents = ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
        $paidCents = $this->toCents($order['amount_paid'] ?? '0.00');
        $totalCents = $this->toCents($order['total_price']);
        $remainingCents = $totalCents - $paidCents;
        if ($paymentCents < 1 || $paymentCents > $remainingCents) {
            return redirect()->back()->with('error', 'Payment must be greater than zero and cannot exceed the remaining balance.');
        }

        $paymentMethod = trim((string) $this->request->getPost('payment_method'));
        if (!in_array($paymentMethod, ['Cash', 'GCash', 'Bank Transfer', 'Check'], true)) {
            return redirect()->back()->with('error', 'Select a valid payment method.');
        }

        $newPaidCents = $paidCents + $paymentCents;
        $balanceCents = $totalCents - $newPaidCents;
        $paymentStatus = $balanceCents === 0 ? 'Paid in Full' : ($newPaidCents > 0 ? 'Partially Paid' : 'Pending');
        $formatCents = static fn ($cents) => intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
        $db = db_connect();
        $db->transStart();
        $db->table('payments')->insert([
            'order_id' => $id,
            'amount' => $formatCents($paymentCents),
            'payment_method' => $paymentMethod,
            'payment_date' => date('Y-m-d H:i:s'),
            'reference_number' => trim((string) $this->request->getPost('reference_number')) ?: null,
            'recorded_by' => session()->get('user_id') ?: null,
        ]);
        $orderModel->update($id, [
            'amount_paid' => $formatCents($newPaidCents),
            'balance_due' => $formatCents($balanceCents),
            'payment_status' => $paymentStatus,
            'payment_method' => $paymentMethod,
        ]);
        $db->table('notifications')->insert([
            'customer_id' => $order['customer_id'] ?? null,
            'order_id' => $id,
            'notification_type' => 'Payment Recorded',
            'message' => 'A payment of PHP ' . $formatCents($paymentCents) . ' was recorded for reservation #' . $id . '.',
        ]);
        $db->table('audit_logs')->insert([
            'user_id' => session()->get('user_id') ?: null,
            'action' => 'Payment Recorded',
            'entity_type' => 'Reservation',
            'entity_id' => (string) $id,
            'old_value' => json_encode(['amount_paid' => $formatCents($paidCents), 'balance_due' => $formatCents($remainingCents)]),
            'new_value' => json_encode(['amount_paid' => $formatCents($newPaidCents), 'balance_due' => $formatCents($balanceCents), 'payment_method' => $paymentMethod]),
        ]);
        $db->transComplete();

        if (!$db->transStatus()) {
            return redirect()->back()->with('error', 'Unable to record the payment. Please try again.');
        }

        return redirect()->to('/ownerdashboard')->with('success', 'Payment recorded and reservation balance updated.');
    }

    // Toggle Order Status (e.g. Pending -> Confirmed)
    public function updateOrderStatus($id)
    {
        if (!$this->hasOwnerAccess()) {
            return service('response')->setStatusCode(403)->setBody('Owner access is required.');
        }

        $status = trim((string) $this->request->getPost('status'));
        $allowedTransitions = [
            'Pending' => ['Confirmed', 'Cancelled'],
            'Confirmed' => ['Processing', 'Cancelled'],
            'Processing' => ['Completed', 'Cancelled'],
        ];
        if (!in_array($status, ['Confirmed', 'Processing', 'Completed', 'Cancelled'], true)) {
            return redirect()->back()->with('error', 'Select a valid reservation status.');
        }

        $orderModel = new OrderModel();
        $order = $orderModel->find($id);
        if (!$order) {
            return redirect()->back()->with('error', 'Reservation not found.');
        }

        if (!in_array($status, $allowedTransitions[$order['status']] ?? [], true)) {
            return redirect()->back()->with('error', 'That reservation status change is not allowed.');
        }

        $db = db_connect();
        if ($status === 'Confirmed') {
            if ((int) round((float) $order['amount_paid'] * 100) < (int) round((float) $order['down_payment_due'] * 100)) {
                return redirect()->back()->with('error', 'Record the required 50% down payment before confirming this reservation.');
            }

            $conflict = $db->table('events')
                ->where('event_date', $order['event_date'])
                ->where('schedule_status', 'Confirmed')
                ->where('order_id !=', $id)
                ->where('start_time <', $order['event_end_time'])
                ->where('end_time >', $order['event_start_time'])
                ->countAllResults();
            if ($conflict > 0) {
                return redirect()->back()->with('error', 'This event time conflicts with another confirmed reservation.');
            }
        }

        $db->transStart();
        $orderModel->update($id, ['status' => $status]);
        $eventStatus = match ($status) {
            'Confirmed' => 'Confirmed',
            'Completed' => 'Completed',
            'Cancelled' => 'Cancelled',
            default => 'Tentative',
        };
        $db->table('events')->where('order_id', $id)->update(['schedule_status' => $eventStatus]);
        $db->transComplete();

        if (!$db->transStatus()) {
            return redirect()->back()->with('error', 'Unable to update the reservation. Please try again.');
        }

        return redirect()->to('/ownerdashboard')->with('success', 'Reservation status updated.');
    }

    private function hasOwnerAccess(): bool
    {
        $ownerSession = session();
        if ($ownerSession->get('role') !== 'Admin') {
            return false;
        }
        $now = time();
        $lastActivity = $ownerSession->get('last_activity');
        if (!is_numeric($lastActivity) || $now - (int) $lastActivity > 86400) {
            $ownerSession->destroy();
            return false;
        }
        $ownerSession->set('last_activity', $now);
        return true;
    }

    private function toCents($amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', (string) $amount, 2), 2, '0');
        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }
}
