<?php

namespace App\Controllers;
use App\Models\MenuModel;
use App\Models\OrderModel;

class Menu extends BaseController
{
    public function index()
    {
        $menuModel = new MenuModel();
        $data['menu_items'] = $menuModel->findAll();
        return view('menu', $data);
    }

    public function submitOrder()
    {
        $clientName = trim((string) $this->request->getPost('client_name'));
        $clientEmail = trim((string) $this->request->getPost('client_email'));
        $clientMobile = trim((string) $this->request->getPost('client_mobile'));
        $deliveryAddress = trim((string) $this->request->getPost('delivery_address'));
        if ($clientName === '' || mb_strlen($clientName) > 120 || !filter_var($clientEmail, FILTER_VALIDATE_EMAIL)
            || mb_strlen($clientEmail) > 254 || !preg_match('/^[0-9+() .-]{7,30}$/', $clientMobile)
            || $deliveryAddress === '' || mb_strlen($deliveryAddress) > 500) {
            return redirect()->back()->withInput()->with('error', 'Enter a valid name, email address, mobile number, and venue address.');
        }

        $paymentMethod = trim((string) ($this->request->getPost('payment_method') ?: 'Cash'));
        if (!in_array($paymentMethod, ['Cash', 'GCash', 'Bank Transfer', 'Check'], true)) {
            return redirect()->back()->withInput()->with('error', 'Select a valid payment method.');
        }

        $eventDate = trim((string) $this->request->getPost('event_date'));
        $startTime = trim((string) $this->request->getPost('event_start_time'));
        $endTime = trim((string) $this->request->getPost('event_end_time'));
        $diningTime = trim((string) $this->request->getPost('dining_time'));
        $guestCount = filter_var($this->request->getPost('guest_count'), FILTER_VALIDATE_INT);
        $parsedDate = \DateTime::createFromFormat('!Y-m-d', $eventDate);
        $validTime = static fn ($time) => preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time) === 1;
        if (!$parsedDate || $parsedDate->format('Y-m-d') !== $eventDate || $eventDate < date('Y-m-d')
            || !$validTime($startTime) || !$validTime($endTime) || !$validTime($diningTime)
            || $endTime <= $startTime || $diningTime < $startTime || $diningTime > $endTime
            || $guestCount === false || $guestCount < 1) {
            return redirect()->back()->withInput()->with('error', 'Enter a valid future event date, guest count, and event schedule.');
        }

        $submittedItems = json_decode((string) $this->request->getPost('selected_items'), true);
        if (!is_array($submittedItems) || $submittedItems === []) {
            return redirect()->back()->withInput()->with('error', 'Add at least one menu item to your bag.');
        }

        $quantities = [];
        foreach ($submittedItems as $item) {
            if (!is_array($item)) {
                return redirect()->back()->withInput()->with('error', 'Your bag could not be read. Please review your selections.');
            }
            $itemId = filter_var($item['id'] ?? null, FILTER_VALIDATE_INT);
            $quantity = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT);
            if ($itemId === false || $itemId < 1 || $quantity === false || $quantity < 1 || $quantity > 99) {
                return redirect()->back()->withInput()->with('error', 'Your bag contains an invalid item quantity.');
            }
            $quantities[$itemId] = ($quantities[$itemId] ?? 0) + $quantity;
            if ($quantities[$itemId] > 99) {
                return redirect()->back()->withInput()->with('error', 'Each menu item is limited to 99 units per order.');
            }
        }

        $menuModel = new MenuModel();
        $menuItems = $menuModel->whereIn('id', array_keys($quantities))->findAll();
        if (count($menuItems) !== count($quantities)) {
            return redirect()->back()->withInput()->with('error', 'A menu item in your bag is no longer available. Please review your selections.');
        }

        $totalCents = 0;
        $itemsById = [];
        foreach ($menuItems as $item) {
            $itemsById[$item['id']] = $item;
            $priceParts = explode('.', (string) $item['price'], 2);
            $priceCents = ((int) $priceParts[0] * 100) + (int) str_pad($priceParts[1] ?? '0', 2, '0');
            $totalCents += $priceCents * $quantities[$item['id']];
        }

        $formatCents = static fn ($cents) => intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
        $totalPrice = $formatCents($totalCents);
        $depositCents = intdiv($totalCents + 1, 2);
        $depositDue = $formatCents($depositCents);

        $orderModel = new OrderModel();
        $db = db_connect();
        $db->transStart();
        $orderId = $orderModel->insert([
            'client_name' => $clientName,
            'client_email' => $clientEmail,
            'client_mobile' => $clientMobile,
            'delivery_address' => $deliveryAddress,
            'event_date' => $eventDate,
            'event_start_time' => $startTime . ':00',
            'event_end_time' => $endTime . ':00',
            'dining_time' => $diningTime . ':00',
            'guest_count' => $guestCount,
            'occasion' => trim((string) $this->request->getPost('occasion')) ?: null,
            'motif' => trim((string) $this->request->getPost('motif')) ?: null,
            'special_requests' => trim((string) $this->request->getPost('special_requests')) ?: null,
            'dining_tier' => 'Menu Order',
            'total_price' => $totalPrice,
            'down_payment_due' => $depositDue,
            'amount_paid' => '0.00',
            'balance_due' => $totalPrice,
            'payment_method' => $paymentMethod,
            'payment_status' => 'Pending',
            'status' => 'Pending',
        ], true);

        if ($orderId === false) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('error', 'Unable to save your booking. Please try again.');
        }

        $orderLines = [];
        foreach ($quantities as $itemId => $quantity) {
            $orderLines[] = [
                'order_id' => $orderId,
                'menu_item_id' => $itemId,
                'item_name_snapshot' => $itemsById[$itemId]['name'],
                'unit_price_snapshot' => $itemsById[$itemId]['price'],
                'quantity' => $quantity,
            ];
        }
        $db->table('order_items')->insertBatch($orderLines);
        $db->table('events')->insert([
            'order_id' => $orderId,
            'event_date' => $eventDate,
            'start_time' => $startTime . ':00',
            'end_time' => $endTime . ':00',
            'venue' => $deliveryAddress,
            'schedule_status' => 'Tentative',
        ]);
        $db->transComplete();

        if (!$db->transStatus()) {
            return redirect()->back()->withInput()->with('error', 'Unable to save your booking. Please try again.');
        }

        return redirect()->to('/menu')->with('success', 'Order submitted! The owner will review your request and contact you to arrange payment.');
    }
}