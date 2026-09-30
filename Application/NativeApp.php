<?php

declare(strict_types=1);

function rc_boot(): void
{
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    // Owner and customer pages share one host, so give each portal its own
    // session cookie. This keeps authentication and CSRF state independent
    // even when both portals are open in separate tabs.
    $portal = defined('RC_PORTAL') ? (string) RC_PORTAL : 'legacy';
    session_name($portal === 'owner' ? 'RIZ_OWNER_SESSID' : ($portal === 'customer' ? 'RIZ_CUSTOMER_SESSID' : 'RIZ_SESSID'));
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params(['httponly' => true, 'secure' => $secure, 'samesite' => 'Lax']);
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (!isset($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
}

function rc_db(): PDO
{
    static $pdo;
    if (!$pdo) {
        $host = getenv('RIZ_DB_HOST') ?: 'localhost';
        $database = getenv('RIZ_DB_NAME') ?: 'riz_catering';
        $username = getenv('RIZ_DB_USER') ?: 'root';
        $password = getenv('RIZ_DB_PASSWORD') ?: '';
        $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

function rc_e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function rc_uploaded_menu_image(string $field = 'image_file'): ?string
{
    $file = $_FILES[$field] ?? null;
    if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || (int) ($file['size'] ?? 0) > 5 * 1024 * 1024 || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
        throw new RuntimeException('Choose an image smaller than 5 MB and try again.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime]) || @getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('Use a valid JPG, PNG, or WebP image.');
    }
    $relativeDirectory = 'uploads/menu';
    $directory = __DIR__ . '/../' . $relativeDirectory;
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('The menu image folder is not writable.');
    }
    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) {
        throw new RuntimeException('The image could not be saved. Check the uploads folder permissions.');
    }
    return $relativeDirectory . '/' . $filename;
}

function rc_url(string $page = 'home', array $params = []): string
{
    if ($page === 'dashboard' && rc_portal() === 'owner') $page = 'owner';
    $ownerPaths = [
        'owner'=>'dashboard','orders'=>'orders','operations'=>'operations','feedback-admin'=>'feedback',
        'menu-admin'=>'menu','packages-admin'=>'packages','sales'=>'sales','settings'=>'settings',
        'customers'=>'customers','users'=>'users','audit'=>'audit','calendar'=>'calendar','reports'=>'reports','owner-password'=>'password',
        'notifications'=>'notifications','offerings'=>'offerings','database'=>'database','owner-login'=>'login','install'=>'register',
    ];
    $base = rc_app_base_path();
    if (isset($ownerPaths[$page])) {
        $path = ($base === '' || $base === '.' ? '' : $base) . '/owner/' . $ownerPaths[$page];
        return $path . ($params ? '?' . http_build_query($params) : '');
    }
    $customerRoot = ($base === '' || $base === '.' ? '' : $base) . '/customer/';
    if ($page === 'home' && !$params) return $customerRoot;
    return $customerRoot . 'index.php?' . http_build_query(['page' => $page] + $params);
}

function rc_app_base_path(): string
{
    $directory = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
    if (preg_match('~/(?:customer|owner)$~i', $directory)) $directory = dirname($directory);
    return $directory === '.' ? '' : rtrim($directory, '/');
}

function rc_portal(): string
{
    if (defined('RC_PORTAL')) return RC_PORTAL === 'owner' ? 'owner' : 'customer';
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    return preg_match('~/owner(?:/|$)~i', $script) ? 'owner' : 'customer';
}

function rc_asset_url(string $path): string
{
    if (preg_match('~^https?://~i', $path)) return $path;
    $base = rc_app_base_path();
    return ($base === '' || $base === '.' ? '' : $base) . '/' . ltrim($path, '/');
}

function rc_endpoint(): string
{
    $base = rc_app_base_path();
    $portal = rc_portal();
    return ($base === '' || $base === '.' ? '' : $base) . '/' . $portal . '/index.php';
}

function rc_redirect(string $page = 'home', array $params = []): never
{
    // Keep the selected catering mode and package when validation sends a
    // reservation back to its form, so the customer does not restart it.
    if ($page === 'reservation' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'reserve') {
        $oldReservation = $_POST;
        unset($oldReservation['csrf'], $oldReservation['action']);
        $_SESSION['reservation_old'] = $oldReservation;
        $orderType = (string) ($_POST['order_type'] ?? 'alacarte');
        if (in_array($orderType, ['alacarte', 'packed_meal', 'buffet', 'custom'], true)) $params['order_type'] ??= $orderType;
        $packageId = filter_var($_POST['package_id'] ?? null, FILTER_VALIDATE_INT);
        if ($packageId && $packageId > 0) $params['package'] ??= $packageId;
    }
    header('Location: ' . rc_url($page, $params));
    exit;
}

function rc_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function rc_take_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function rc_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function rc_require_login(): array
{
    $user = rc_user();
    if (!$user) {
        rc_flash('error', 'Please log in to continue.');
        rc_redirect('login');
    }
    $stmt = rc_db()->prepare('SELECT full_name, email, role, status FROM users WHERE user_id=?');
    $stmt->execute([$user['user_id']]);
    $account = $stmt->fetch();
    if (!$account || $account['status'] !== 'Active' || !in_array($account['role'], ['Customer', 'Admin'], true)) {
        unset($_SESSION['user']);
        session_regenerate_id(true);
        rc_flash('error', 'This account is inactive. Contact the owner.');
        rc_redirect('login');
    }
    $_SESSION['user'] = array_merge($user, ['full_name' => $account['full_name'], 'email' => $account['email'], 'role' => $account['role']]);
    return $_SESSION['user'];
}

function rc_require_owner(): array
{
    if (!rc_user()) rc_redirect('owner-login');
    $user = rc_require_login();
    if ($user['role'] !== 'Admin') {
        http_response_code(403);
        rc_render('error', ['title' => 'Access denied', 'message' => 'This area is only available to the owner.']);
        exit;
    }
    return $user;
}

function rc_csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . rc_e($_SESSION['csrf']) . '">';
}

function rc_verify_csrf(): void
{
    $token = (string) ($_POST['csrf'] ?? '');
    if (!hash_equals((string) ($_SESSION['csrf'] ?? ''), $token)) {
        http_response_code(400);
        exit('Invalid request token. Reload the page and try again.');
    }
}

function rc_money(int $cents): string
{
    return '₱' . number_format($cents / 100, 2);
}

function rc_cents($amount): int
{
    $amount = (string) $amount;
    if (!preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $amount, $matches)) {
        return 0;
    }
    return ((int) $matches[1] * 100) + (int) str_pad($matches[2] ?? '0', 2, '0');
}

function rc_money_db(int $cents): string
{
    return intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
}

function rc_enforce_owner_timeout(): void
{
    $user = rc_user();
    if (($user['role'] ?? '') !== 'Admin') {
        return;
    }
    $now = time();
    $lastActivity = (int) ($_SESSION['last_activity'] ?? $now);
    if ($now - $lastActivity > 86400) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', $now - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        header('Location: ' . rc_url('owner-login', ['reason' => 'expired']));
        exit;
    }
    $_SESSION['last_activity'] = $now;
}

function rc_owner_setup_locked(PDO $db): bool
{
    $ownerExists = (int) $db->query("SELECT COUNT(*) FROM users WHERE role='Admin'")->fetchColumn() > 0;
    $initialized = $db->query("SELECT setting_value FROM system_settings WHERE setting_key='owner_account_created'")->fetchColumn() === 'true';
    return $ownerExists || $initialized;
}

function rc_csv_text($value): string
{
    $text = (string) $value;
    return preg_match('/^[\t\r ]*[=+@-]/', $text) ? "'" . $text : $text;
}

function rc_audit(?int $userId, string $action, string $entity, string $id, $old = null, $new = null): void
{
    $stmt = rc_db()->prepare('INSERT INTO audit_logs (user_id, action, entity_type, entity_id, old_value, new_value) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $action, $entity, $id, $old === null ? null : json_encode($old), $new === null ? null : json_encode($new)]);
}

function rc_notify(?int $userId, ?int $customerId, ?int $orderId, string $type, string $message): void
{
    $stmt = rc_db()->prepare('INSERT INTO notifications (user_id, customer_id, order_id, notification_type, message) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $customerId, $orderId, $type, $message]);
}

function rc_notify_owner(?int $orderId, string $type, string $message): void
{
    $owners = rc_db()->query("SELECT user_id FROM users WHERE status='Active' AND role='Admin'")->fetchAll(PDO::FETCH_COLUMN);
    $stmt = rc_db()->prepare('INSERT INTO notifications (user_id, order_id, notification_type, message) VALUES (?, ?, ?, ?)');
    foreach ($owners as $userId) $stmt->execute([(int) $userId, $orderId, $type, $message]);
}

function rc_dispatch(): void
{
    try {
        rc_enforce_owner_timeout();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            rc_verify_csrf();
            rc_action((string) ($_POST['action'] ?? ''));
            return;
        }

        $page = (string) ($_GET['page'] ?? 'home');
        $user = rc_user();
        $ownerPages = ['owner','orders','operations','feedback-admin','menu-admin','packages-admin','sales','settings','customers','users','audit','calendar','reports','notifications','offerings','database'];
        $customerPages = ['home','menu','feedback','login','register','reservation','reservation-view','guest-confirmation','dashboard'];
        $isCustomerPath = preg_match('~(?:^|/)customer(?:/|$)~i', (string)(parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '')) === 1;
        $requestPath = (string) (parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '');
        $isOwnerPath = preg_match('~(?:^|/)owner(?:/|$)~i', $requestPath) === 1;
        if ($isCustomerPath && (in_array($page, $ownerPages, true) || in_array($page, ['owner-login','install'], true))) {
            if (($user['role'] ?? '') === 'Admin') rc_redirect($page);
            rc_redirect('home');
        }
        if (!$user && $isOwnerPath && !in_array($page, ['owner-login','install'], true)) rc_redirect('owner-login');
        if (($user['role'] ?? '') === 'Customer' && $isOwnerPath) rc_redirect('dashboard');
        if (($user['role'] ?? '') === 'Admin' && in_array($page, $customerPages, true)) rc_redirect('owner');
        if (($user['role'] ?? '') === 'Customer' && in_array($page, $ownerPages, true)) rc_redirect('dashboard');
        if (($user['role'] ?? '') === 'Customer' && $page === 'owner-login') rc_redirect('dashboard');
        $db = rc_db();
        $data = ['user' => $user, 'query' => trim((string) ($_GET['q'] ?? ''))];
        $data['businessName'] = (string) ($db->query("SELECT setting_value FROM system_settings WHERE setting_key='business_name'")->fetchColumn() ?: 'Riz Catering Services');
        if (($user['role'] ?? '') === 'Admin') {
            $noticeStmt = $db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND read_at IS NULL');
            $noticeStmt->execute([$user['user_id']]);
            $data['navUnreadNotifications'] = (int) $noticeStmt->fetchColumn();
            $data['ownerPendingCount'] = (int) $db->query("SELECT COUNT(*) FROM orders WHERE status='Pending'")->fetchColumn();
        } elseif (($user['role'] ?? '') === 'Customer') {
            $customer = rc_customer_for($user);
            $noticeStmt = $db->prepare('SELECT COUNT(*) FROM notifications WHERE customer_id=? AND read_at IS NULL');
            $noticeStmt->execute([$customer['customer_id']]);
            $data['navUnreadNotifications'] = (int) $noticeStmt->fetchColumn();
        }

        switch ($page) {
            case 'home':
                $data['packages'] = $db->query("SELECT * FROM catering_packages WHERE status = 'Active' ORDER BY package_name")->fetchAll();
                $data['items'] = $db->query('SELECT * FROM menu_items WHERE is_active = 1 ORDER BY category, name LIMIT 6')->fetchAll();
                rc_render('home', $data);
                break;
            case 'menu':
                $packageType = (string) ($_GET['package_type'] ?? '');
                if (!in_array($packageType, ['packed_meal', 'buffet'], true)) $packageType = '';
                $data['selectedPackageType'] = $packageType;
                $category = trim((string) ($_GET['category'] ?? ''));
                if ($packageType !== '') {
                    $stmt = $db->prepare("SELECT * FROM catering_packages WHERE status = 'Active' AND package_type = ? ORDER BY package_name");
                    $stmt->execute([$packageType]);
                    $data['packages'] = $stmt->fetchAll();
                    $data['items'] = [];
                } elseif ($category !== '') {
                    $data['packages'] = [];
                    $stmt = $db->prepare('SELECT * FROM menu_items WHERE is_active = 1 AND category = ? ORDER BY name');
                    $stmt->execute([$category]);
                    $data['items'] = $stmt->fetchAll();
                } else {
                    $data['packages'] = $db->query("SELECT * FROM catering_packages WHERE status = 'Active' ORDER BY package_name")->fetchAll();
                    $data['items'] = $db->query('SELECT * FROM menu_items WHERE is_active = 1 ORDER BY category, name')->fetchAll();
                }
                if ($data['query'] !== '' && $packageType !== '') {
                    $term = '%' . $data['query'] . '%';
                    $stmt = $db->prepare("SELECT * FROM catering_packages WHERE status = 'Active' AND package_type = ? AND (package_name LIKE ? OR description LIKE ?) ORDER BY package_name");
                    $stmt->execute([$packageType, $term, $term]);
                    $data['packages'] = $stmt->fetchAll();
                } elseif ($data['query'] !== '') {
                    $term = '%' . $data['query'] . '%';
                    $stmt = $db->prepare('SELECT * FROM menu_items WHERE is_active = 1 AND (name LIKE ? OR description LIKE ? OR category LIKE ?) ORDER BY category, name');
                    $stmt->execute([$term, $term, $term]);
                    $searchItems = $stmt->fetchAll();
                    if ($category !== '') $searchItems = array_values(array_filter($searchItems, static fn ($item) => strcasecmp((string) $item['category'], $category) === 0));
                    else $data['packages'] = [];
                    $data['items'] = $searchItems;
                }
                $stmt = $db->query('SELECT DISTINCT category FROM menu_items WHERE is_active = 1 AND category IS NOT NULL AND category <> "" ORDER BY category');
                $data['categories'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
                rc_render('menu', $data);
                break;
            case 'register':
            case 'login':
                rc_render($page, $data);
                break;
            case 'owner-login':
                if (($user['role'] ?? '') === 'Admin') rc_redirect('owner');
                $data['ownerSetupAvailable'] = !rc_owner_setup_locked($db);
                $data['setupConfigured'] = (string) getenv('RIZ_OWNER_SETUP_KEY') !== '';
                rc_render('owner_login', $data);
                break;
            case 'install':
                if (rc_owner_setup_locked($db)) {
                    rc_redirect('owner-login');
                }
                $data['setupConfigured'] = (string) getenv('RIZ_OWNER_SETUP_KEY') !== '';
                rc_render('install', $data);
                break;
            case 'dashboard':
                $user = rc_require_login();
                if ($user['role'] === 'Admin') rc_redirect('owner');
                $customer = rc_customer_for($user);
                $data['customer'] = $customer;
                $stmt = $db->prepare('SELECT o.*, p.package_name FROM orders o LEFT JOIN catering_packages p ON p.package_id = o.package_id WHERE o.customer_id = ? ORDER BY o.created_at DESC');
                $stmt->execute([$customer['customer_id']]);
                $data['orders'] = $stmt->fetchAll();
                rc_create_reminders($customer['customer_id']);
                $stmt = $db->prepare('SELECT * FROM notifications WHERE customer_id = ? ORDER BY created_at DESC LIMIT 30');
                $stmt->execute([$customer['customer_id']]);
                $data['notifications'] = $stmt->fetchAll();
                rc_render('dashboard', $data);
                break;
            case 'reservation':
                $customer = ($user['role'] ?? '') === 'Customer' ? rc_customer_for(rc_require_login()) : null;
                $data['customer'] = $customer;
                $data['reservationOld'] = $_SESSION['reservation_old'] ?? [];
                unset($_SESSION['reservation_old']);
                $data['packages'] = $db->query("SELECT * FROM catering_packages WHERE status = 'Active' ORDER BY package_name")->fetchAll();
                $data['items'] = $db->query('SELECT id, name, description, category, price, unit_type, component_type, image_url FROM menu_items WHERE is_active=1 ORDER BY category,name')->fetchAll();
                $data['packageOptions'] = $db->query("SELECT pi.package_id, cp.package_type, pi.option_group, pi.component_type, pi.selection_rule, pi.additional_charge, pi.charge_type, mi.id, mi.name, mi.description, mi.price, mi.category, mi.unit_type, mi.component_type AS item_component_type FROM package_items pi JOIN catering_packages cp ON cp.package_id=pi.package_id JOIN menu_items mi ON mi.id = pi.menu_item_id WHERE mi.is_active = 1 ORDER BY pi.package_id, pi.option_group, mi.name")->fetchAll();
                $data['packageComponentRules'] = $db->query('SELECT package_id, component_type, minimum_quantity, maximum_quantity FROM package_component_rules')->fetchAll();
                $data['requestedPackage'] = (int) ($_GET['package'] ?? 0);
                rc_render('reservation', $data);
                break;
            case 'guest-confirmation':
                if ($user) rc_redirect($user['role'] === 'Admin' ? 'owner' : 'dashboard');
                $guestOrderId = (int) ($_SESSION['guest_order_id'] ?? 0);
                if ($guestOrderId < 1) rc_redirect('menu');
                $stmt = $db->prepare('SELECT o.*, p.package_name FROM orders o LEFT JOIN catering_packages p ON p.package_id=o.package_id WHERE o.id=? AND o.customer_id IS NULL');
                $stmt->execute([$guestOrderId]);
                $data['order'] = $stmt->fetch();
                if (!$data['order']) rc_redirect('menu');
                $stmt = $db->prepare('SELECT * FROM order_items WHERE order_id=? ORDER BY id');
                $stmt->execute([$guestOrderId]);
                $data['orderItems'] = $stmt->fetchAll();
                rc_render('guest-confirmation', $data);
                break;
            case 'reservation-view':
                $user = rc_require_login();
                $order = rc_order_for_user((int) ($_GET['id'] ?? 0), $user);
                $data['order'] = $order;
                $stmt = $db->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id');
                $stmt->execute([$order['id']]);
                $data['orderItems'] = $stmt->fetchAll();
                $stmt = $db->prepare("SELECT * FROM feedback WHERE order_id = ? AND customer_id = ? LIMIT 1");
                $stmt->execute([$order['id'], $order['customer_id']]);
                $data['feedback'] = $stmt->fetch() ?: null;
                rc_render('reservation-view', $data);
                break;
            case 'feedback':
                $data['feedbackEntries'] = $db->query("SELECT f.rating, f.comments, f.image_path, f.submitted_at, c.full_name AS reviewer_name, p.package_name FROM feedback f JOIN customers c ON c.customer_id=f.customer_id JOIN orders o ON o.id=f.order_id LEFT JOIN catering_packages p ON p.package_id=o.package_id WHERE f.status='Reviewed' ORDER BY f.submitted_at DESC LIMIT 100")->fetchAll();
                $data['eligibleOrders'] = [];
                if (($user['role'] ?? '') === 'Customer') {
                    $customer = rc_customer_for($user);
                    $stmt = $db->prepare("SELECT o.id, o.event_date, p.package_name FROM orders o LEFT JOIN catering_packages p ON p.package_id=o.package_id WHERE o.customer_id=? AND o.status='Completed' AND NOT EXISTS (SELECT 1 FROM feedback f WHERE f.order_id=o.id AND f.customer_id=o.customer_id) ORDER BY o.event_date DESC");
                    $stmt->execute([$customer['customer_id']]);
                    $data['eligibleOrders'] = $stmt->fetchAll();
                }
                rc_render('feedback', $data);
                break;
            case 'owner':
                rc_require_owner();
                $data['ownerSummary'] = $db->query("SELECT COUNT(*) AS total_orders, SUM(status='Pending') AS pending_orders, SUM(status='Confirmed') AS confirmed_orders, SUM(status='Processing') AS processing_orders, SUM(status='Completed') AS completed_orders, COALESCE(SUM(CASE WHEN status IN ('Confirmed','Processing','Completed') THEN total_price ELSE 0 END),0) AS booked_sales, COALESCE(SUM(amount_paid),0) AS collected, COALESCE(SUM(CASE WHEN status NOT IN ('Cancelled','Rejected') THEN balance_due ELSE 0 END),0) AS outstanding FROM orders")->fetch();
                $data['upcomingEvents'] = $db->query("SELECT e.*, o.client_name, o.client_mobile, o.guest_count, o.status, o.payment_status, o.total_price, o.amount_paid, o.balance_due, p.package_name FROM events e JOIN orders o ON o.id=e.order_id LEFT JOIN catering_packages p ON p.package_id=o.package_id WHERE e.event_date >= CURDATE() AND e.schedule_status='Confirmed' ORDER BY e.event_date, e.start_time LIMIT 8")->fetchAll();
                $data['upcomingEventsCount'] = (int) $db->query("SELECT COUNT(*) FROM events WHERE event_date>=CURDATE() AND schedule_status='Confirmed'")->fetchColumn();
                $data['recentOrders'] = $db->query('SELECT o.id, o.client_name, o.created_at, o.event_date, o.status, o.payment_status, o.total_price, p.package_name FROM orders o LEFT JOIN catering_packages p ON p.package_id=o.package_id ORDER BY o.created_at DESC LIMIT 8')->fetchAll();
                $data['newFeedbackCount'] = (int) $db->query("SELECT COUNT(*) FROM feedback WHERE status='New'")->fetchColumn();
                $noticeStmt = $db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND read_at IS NULL');
                $noticeStmt->execute([$user['user_id']]);
                $data['unreadNotifications'] = (int) $noticeStmt->fetchColumn();
                $period = in_array($_GET['period'] ?? '', ['today', 'week', 'month', 'year'], true) ? (string) $_GET['period'] : 'month';
                $data['period'] = $period;
                $today = new DateTimeImmutable('today');
                [$start, $end, $group, $label] = match ($period) {
                    'today' => [$today, $today->modify('+1 day'), 'DATE_FORMAT(payment_date, "%H:00")', 'DATE_FORMAT(payment_date, "%H:00")'],
                    'week' => [$today->modify('monday this week'), $today->modify('monday this week')->modify('+1 week'), 'DATE(payment_date)', 'DATE_FORMAT(payment_date, "%a %e")'],
                    'year' => [new DateTimeImmutable(date('Y-01-01')), (new DateTimeImmutable(date('Y-01-01')))->modify('+1 year'), 'DATE_FORMAT(payment_date, "%Y-%m")', 'DATE_FORMAT(payment_date, "%b")'],
                    default => [new DateTimeImmutable(date('Y-m-01')), (new DateTimeImmutable(date('Y-m-01')))->modify('+1 month'), 'DATE(payment_date)', 'DATE_FORMAT(payment_date, "%b %e")'],
                };
                $trendStmt = $db->prepare("SELECT $label AS label, SUM(amount) AS total FROM payments WHERE status='Recorded' AND payment_date >= ? AND payment_date < ? GROUP BY $group ORDER BY MIN(payment_date)");
                $trendStmt->execute([$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')]);
                $data['salesTrend'] = $trendStmt->fetchAll();
                $data['todayEvents'] = (int) $db->query("SELECT COUNT(*) FROM events WHERE event_date=CURDATE() AND schedule_status='Confirmed'")->fetchColumn();
                rc_render('owner', $data);
                break;
            case 'database':
                rc_require_owner();
                $data['databaseSections'] = [];
                $queries = [
                    'INNER JOIN · Orders with packages' => "SELECT o.id AS order_id,o.client_name,p.package_name,o.status,o.total_price FROM orders o INNER JOIN catering_packages p ON p.package_id=o.package_id ORDER BY o.created_at DESC LIMIT 8",
                    'LEFT JOIN · Every package, including unused ones' => "SELECT p.package_name,COUNT(o.id) AS reservations,COALESCE(SUM(o.total_price),0) AS order_value FROM catering_packages p LEFT JOIN orders o ON o.package_id=p.package_id GROUP BY p.package_id,p.package_name ORDER BY reservations DESC,p.package_name LIMIT 12",
                    'RIGHT JOIN · Every package and its menu choices' => "SELECT p.package_name,mi.name AS menu_item,pi.option_group FROM package_items pi RIGHT JOIN catering_packages p ON p.package_id=pi.package_id LEFT JOIN menu_items mi ON mi.id=pi.menu_item_id ORDER BY p.package_name,mi.name LIMIT 15",
                    'GROUP BY + aggregate · Package performance' => "SELECT package_name,reservation_count,booked_revenue,average_guests FROM vw_package_performance ORDER BY booked_revenue DESC LIMIT 12",
                    'HAVING · Categories with multiple menu items' => "SELECT category,COUNT(*) AS item_count,ROUND(AVG(price),2) AS average_price FROM menu_items WHERE category IS NOT NULL GROUP BY category HAVING COUNT(*)>1 ORDER BY item_count DESC",
                    'GROUP BY + HAVING + aggregates · Monthly order totals' => "SELECT DATE_FORMAT(event_date,'%Y-%m') AS month,COUNT(*) AS reservations,SUM(total_price) AS requested_value,AVG(guest_count) AS average_guests FROM orders GROUP BY DATE_FORMAT(event_date,'%Y-%m') HAVING COUNT(*)>=1 ORDER BY month DESC LIMIT 12",
                    'WHERE · Upcoming active reservations' => "SELECT id,client_name,event_date,guest_count,status FROM orders WHERE event_date>=CURDATE() AND status IN ('Pending','Confirmed','Processing') ORDER BY event_date LIMIT 12",
                    'UNION · Order values and recorded payments' => "(SELECT CONCAT('Order #',id) AS entry_type,client_name AS label,total_price AS amount,created_at AS entry_date FROM orders) UNION ALL (SELECT CONCAT('Payment #',pay.payment_id),o.client_name,pay.amount,pay.payment_date FROM payments pay INNER JOIN orders o ON o.id=pay.order_id) ORDER BY entry_date DESC LIMIT 15",
                    'Multi-row subquery 1 · Orders for active packages' => "SELECT id,client_name,package_id,status FROM orders WHERE package_id IN (SELECT package_id FROM catering_packages WHERE status='Active') ORDER BY created_at DESC LIMIT 10",
                    'Multi-row subquery 2 · Menu in used categories' => "SELECT id,name,category,price FROM menu_items WHERE category IN (SELECT category FROM menu_items GROUP BY category HAVING COUNT(*)>1) ORDER BY category,name LIMIT 12",
                    'Multi-row subquery 3 · Packages with menu choices' => "SELECT package_id,package_name FROM catering_packages WHERE package_id IN (SELECT package_id FROM package_items) ORDER BY package_name LIMIT 12",
                    'Multi-column subquery · Package and item pairs' => "SELECT package_id,menu_item_id,option_group FROM package_items WHERE (package_id,menu_item_id) IN (SELECT package_id,menu_item_id FROM package_items WHERE selection_rule='Required One') ORDER BY package_id,option_group LIMIT 12",
                    'Correlated subquery 1 · Customers with at least one order' => "SELECT c.customer_id,c.full_name,c.email FROM customers c WHERE EXISTS (SELECT 1 FROM orders o WHERE o.customer_id=c.customer_id) ORDER BY c.full_name LIMIT 12",
                    'Correlated subquery 2 · Packages above their average price' => "SELECT p.package_id,p.package_name,p.package_price FROM catering_packages p WHERE p.package_price>(SELECT AVG(p2.package_price) FROM catering_packages p2 WHERE p2.status=p.status) ORDER BY p.package_price DESC LIMIT 12",
                    'View 1 · Order, customer, and package report' => 'SELECT order_id,client_name,package_name,account_name,event_date,status,total_price FROM vw_order_customer_package ORDER BY event_date DESC LIMIT 12',
                    'View 2 · Package performance across orders and order items' => 'SELECT package_name,package_status,reservation_count,booked_revenue,average_guests,sold_order_lines FROM vw_package_performance ORDER BY booked_revenue DESC LIMIT 12',
                    'View 3 · Customer, account, and order summary' => 'SELECT full_name,email,account_status,order_count,lifetime_order_value FROM vw_customer_order_summary ORDER BY lifetime_order_value DESC LIMIT 12',
                    'View 4 · Event and reservation monitor' => 'SELECT event_id,event_date,start_time,end_time,venue,schedule_status,client_name,guest_count,order_status FROM vw_event_reservation ORDER BY event_date DESC LIMIT 12',
                    'Numeric function · Order balance' => 'SELECT id AS order_id,client_name,fn_order_balance(id) AS balance_from_function FROM orders ORDER BY created_at DESC LIMIT 8',
                    'String function · Customer display label' => 'SELECT customer_id,fn_customer_label(customer_id) AS customer_label FROM customers ORDER BY full_name LIMIT 8',
                    'Business rule function · Ready for confirmation' => 'SELECT id AS order_id,client_name,status,amount_paid,down_payment_due,fn_order_can_confirm(id) AS can_confirm FROM orders ORDER BY created_at DESC LIMIT 8',
                ];
                foreach ($queries as $title => $sql) {
                    $data['databaseSections'][] = ['title' => $title, 'rows' => $db->query($sql)->fetchAll()];
                }
                $data['procedurePanels'] = [];
                $data['databaseRoutineError'] = null;
                $collectProcedure = static function (string $call, array $params) use ($db): array {
                    $stmt = $db->prepare($call);
                    $stmt->execute($params);
                    $resultSets = [];
                    do {
                        if ($stmt->columnCount() > 0) $resultSets[] = $stmt->fetchAll();
                    } while ($stmt->nextRowset());
                    $stmt->closeCursor();
                    return $resultSets;
                };
                try {
                    $orderId = (int) $db->query('SELECT COALESCE(MAX(id),0) FROM orders')->fetchColumn();
                    $packageId = (int) $db->query('SELECT COALESCE(MAX(package_id),0) FROM catering_packages')->fetchColumn();
                    $customerId = (int) $db->query('SELECT COALESCE(MAX(customer_id),0) FROM customers')->fetchColumn();
                    $procedureCalls = [
                        ['sp_order_detail_bundle', 'Order detail bundle · 3 processes', 'CALL sp_order_detail_bundle(?)', [$orderId], ['Order header','Order items','Payment history']],
                        ['sp_package_setup_bundle', 'Package setup bundle · 2 processes', 'CALL sp_package_setup_bundle(?)', [$packageId], ['Package performance','Package menu choices']],
                        ['sp_riz_sales_dashboard', 'Sales dashboard · 3 processes', 'CALL sp_riz_sales_dashboard(?)', [(int) date('Y')], ['Monthly totals','Order status totals','Payment method totals']],
                        ['sp_customer_activity_bundle', 'Customer activity bundle · 3 processes', 'CALL sp_customer_activity_bundle(?)', [$customerId], ['Customer summary','Reservation history','Feedback history']],
                    ];
                    foreach ($procedureCalls as [$name, $title, $call, $params, $labels]) {
                        $sets = $collectProcedure($call, $params);
                        $processes = [];
                        foreach ($labels as $index => $label) $processes[] = ['title' => $label, 'rows' => $sets[$index] ?? []];
                        $data['procedurePanels'][] = ['name' => $name, 'title' => $title, 'processes' => $processes];
                    }
                } catch (Throwable $error) {
                    $data['databaseRoutineError'] = $error->getMessage();
                }
                $data['activityRows'] = $db->query('SELECT table_name,record_id,action_name,details,logged_at FROM database_activity_log ORDER BY log_id DESC LIMIT 12')->fetchAll();
                $data['databaseTriggers'] = $db->query("SELECT TRIGGER_NAME,EVENT_OBJECT_TABLE,EVENT_MANIPULATION,ACTION_TIMING FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND TRIGGER_NAME LIKE 'trg_%' ORDER BY EVENT_OBJECT_TABLE,TRIGGER_NAME")->fetchAll();
                $data['triggerAuditRows'] = $db->query("SELECT created_at,entity_type,entity_id,old_value,new_value FROM audit_logs WHERE action='DATABASE_UPDATE' ORDER BY audit_id DESC LIMIT 12")->fetchAll();
                rc_render('database', $data);
                break;
            case 'orders':
                rc_require_owner();
                $data['filters'] = [
                    'status' => trim((string) ($_GET['status'] ?? '')),
                    'payment' => trim((string) ($_GET['payment'] ?? '')),
                    'date' => trim((string) ($_GET['date'] ?? '')),
                    'from' => rc_valid_date((string) ($_GET['from'] ?? '')) ? (string) $_GET['from'] : '',
                    'to' => rc_valid_date((string) ($_GET['to'] ?? '')) ? (string) $_GET['to'] : '',
                    'package' => filter_var($_GET['package'] ?? null, FILTER_VALIDATE_INT) ?: null,
                    'q' => trim((string) ($_GET['q'] ?? '')),
                ];
                if ($data['filters']['from'] !== '' && $data['filters']['to'] !== '' && $data['filters']['from'] > $data['filters']['to']) {
                    [$data['filters']['from'], $data['filters']['to']] = [$data['filters']['to'], $data['filters']['from']];
                }
                $where = [];
                $params = [];
                if (in_array($data['filters']['status'], ['Pending', 'Confirmed', 'Processing', 'Completed', 'Cancelled', 'Rejected'], true)) { $where[] = 'o.status=?'; $params[] = $data['filters']['status']; }
                if (in_array($data['filters']['payment'], ['Pending', 'Partially Paid', 'Paid in Full'], true)) { $where[] = 'o.payment_status=?'; $params[] = $data['filters']['payment']; }
                if ($data['filters']['date'] === 'upcoming') { $where[] = 'o.event_date>=CURDATE()'; }
                elseif ($data['filters']['date'] === 'today') { $where[] = 'o.event_date=CURDATE()'; }
                elseif ($data['filters']['date'] === 'week') { $where[] = 'o.event_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)'; }
                elseif ($data['filters']['date'] === 'month') { $where[] = 'o.event_date BETWEEN DATE_FORMAT(CURDATE(),"%Y-%m-01") AND LAST_DAY(CURDATE())'; }
                if ($data['filters']['from'] !== '') { $where[] = 'o.event_date >= ?'; $params[] = $data['filters']['from']; }
                if ($data['filters']['to'] !== '') { $where[] = 'o.event_date <= ?'; $params[] = $data['filters']['to']; }
                if ($data['filters']['package']) { $where[] = 'o.package_id=?'; $params[] = $data['filters']['package']; }
                if ($data['filters']['q'] !== '') { $term = '%' . $data['filters']['q'] . '%'; $where[] = '(o.client_name LIKE ? OR o.client_email LIKE ? OR o.client_mobile LIKE ? OR p.package_name LIKE ? OR CAST(o.id AS CHAR) LIKE ?)'; array_push($params, $term, $term, $term, $term, $term); }
                $sql = 'SELECT o.*, p.package_name, p.description AS package_description, p.package_price, p.price_type, c.customer_id AS linked_customer_id FROM orders o LEFT JOIN catering_packages p ON p.package_id=o.package_id LEFT JOIN customers c ON c.customer_id=o.customer_id' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY o.created_at DESC';
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $data['orders'] = $stmt->fetchAll();
                $data['packages'] = $db->query('SELECT package_id, package_name FROM catering_packages ORDER BY package_name')->fetchAll();
                $data['orderItems'] = [];
                $data['orderPayments'] = [];
                if ($data['orders']) {
                    $ids = array_map(static fn ($order) => (int) $order['id'], $data['orders']);
                    $marks = implode(',', array_fill(0, count($ids), '?'));
                    $stmt = $db->prepare("SELECT * FROM order_items WHERE order_id IN ($marks) ORDER BY id");
                    $stmt->execute($ids);
                    foreach ($stmt->fetchAll() as $item) $data['orderItems'][(int) $item['order_id']][] = $item;
                    $stmt = $db->prepare("SELECT * FROM payments WHERE order_id IN ($marks) ORDER BY payment_date DESC");
                    $stmt->execute($ids);
                    foreach ($stmt->fetchAll() as $payment) $data['orderPayments'][(int) $payment['order_id']][] = $payment;
                }
                rc_render('orders', $data);
                break;
            case 'operations':
                rc_require_owner();
                $view = in_array($_GET['view'] ?? 'month', ['month', 'week', 'day'], true) ? (string) $_GET['view'] : 'month';
                $data['view'] = $view;
                $requestedDate = rc_valid_date((string) ($_GET['date'] ?? '')) ? (string) $_GET['date'] : date('Y-m-d');
                $month = preg_match('/^\d{4}-\d{2}$/', (string) ($_GET['month'] ?? '')) ? $_GET['month'] : substr($requestedDate, 0, 7);
                $data['month'] = $month;
                $data['selectedDate'] = $requestedDate;
                if ($view === 'day') {
                    $start = $requestedDate;
                    $endDate = $requestedDate;
                } elseif ($view === 'week') {
                    $day = new DateTimeImmutable($requestedDate);
                    $start = $day->modify('monday this week')->format('Y-m-d');
                    $endDate = $day->modify('sunday this week')->format('Y-m-d');
                } else {
                    $start = $month . '-01';
                    $endDate = date('Y-m-t', strtotime($start));
                }
                $stmt = $db->prepare("SELECT e.*, o.client_name, o.client_mobile, o.guest_count, o.status, o.payment_status, o.total_price, o.amount_paid, o.balance_due, o.occasion, o.special_requests, p.package_name FROM events e JOIN orders o ON o.id=e.order_id LEFT JOIN catering_packages p ON p.package_id=o.package_id WHERE e.event_date BETWEEN ? AND ? AND e.schedule_status NOT IN ('Cancelled','Rejected') ORDER BY e.event_date, e.start_time");
                $stmt->execute([$start, $endDate]);
                $data['events'] = $stmt->fetchAll();
                $data['todayEvents'] = array_values(array_filter($data['events'], static fn ($event) => $event['event_date'] === date('Y-m-d')));
                rc_render('operations', $data);
                break;
            case 'feedback-admin':
                rc_require_owner();
                $data['feedback'] = $db->query('SELECT f.*, o.client_name, o.client_email, o.event_date, o.id AS reservation_id, p.package_name FROM feedback f JOIN orders o ON o.id=f.order_id LEFT JOIN catering_packages p ON p.package_id=o.package_id ORDER BY f.submitted_at DESC')->fetchAll();
                $data['feedbackStats'] = $db->query("SELECT COUNT(*) AS total, COALESCE(AVG(rating),0) AS average, SUM(status='New') AS new_count, SUM(rating=5) AS rating_5, SUM(rating=4) AS rating_4, SUM(rating=3) AS rating_3, SUM(rating=2) AS rating_2, SUM(rating=1) AS rating_1 FROM feedback")->fetch();
                rc_render('feedback-admin', $data);
                break;
            case 'settings':
                rc_require_owner();
                $data['settings'] = $db->query('SELECT setting_key, setting_value FROM system_settings ORDER BY setting_key')->fetchAll(PDO::FETCH_KEY_PAIR);
                rc_render('settings', $data);
                break;
            case 'calendar':
                rc_require_owner();
                $month = preg_match('/^\d{4}-\d{2}$/', (string) ($_GET['month'] ?? '')) ? $_GET['month'] : date('Y-m');
                $data['month'] = $month;
                $stmt = $db->prepare("SELECT e.*, o.client_name, o.guest_count, o.status, p.package_name FROM events e JOIN orders o ON o.id=e.order_id LEFT JOIN catering_packages p ON p.package_id=o.package_id WHERE e.event_date BETWEEN ? AND ? AND e.schedule_status NOT IN ('Cancelled','Rejected') ORDER BY e.event_date, e.start_time");
                $start = $month . '-01';
                $stmt->execute([$start, date('Y-m-t', strtotime($start))]);
                $data['events'] = $stmt->fetchAll();
                rc_render('calendar', $data);
                break;
            case 'reports':
                rc_require_owner();
                rc_redirect('sales');
            case 'sales':
                rc_require_owner();
                $filters = [
                    'from' => rc_valid_date((string) ($_GET['from'] ?? '')) ? (string) $_GET['from'] : '',
                    'to' => rc_valid_date((string) ($_GET['to'] ?? '')) ? (string) $_GET['to'] : '',
                    'status' => in_array($_GET['status'] ?? '', ['Pending', 'Confirmed', 'Processing', 'Completed', 'Cancelled', 'Rejected'], true) ? (string) $_GET['status'] : '',
                    'method' => in_array($_GET['method'] ?? '', ['Cash', 'GCash', 'Bank Transfer', 'Check'], true) ? (string) $_GET['method'] : '',
                    'package' => filter_var($_GET['package'] ?? null, FILTER_VALIDATE_INT) ?: null,
                    'q' => trim((string) ($_GET['q'] ?? '')),
                ];
                if ($filters['from'] !== '' && $filters['to'] !== '' && $filters['from'] > $filters['to']) {
                    [$filters['from'], $filters['to']] = [$filters['to'], $filters['from']];
                }
                $where = [];
                $params = [];
                if ($filters['from'] !== '') { $where[] = 'o.event_date >= ?'; $params[] = $filters['from']; }
                if ($filters['to'] !== '') { $where[] = 'o.event_date <= ?'; $params[] = $filters['to']; }
                if ($filters['status'] !== '') { $where[] = 'o.status = ?'; $params[] = $filters['status']; }
                if ($filters['method'] !== '') { $where[] = 'o.payment_method = ?'; $params[] = $filters['method']; }
                if ($filters['package']) { $where[] = 'o.package_id = ?'; $params[] = $filters['package']; }
                if ($filters['q'] !== '') {
                    $term = '%' . $filters['q'] . '%';
                    $where[] = '(o.client_name LIKE ? OR o.client_mobile LIKE ? OR CAST(o.id AS CHAR) LIKE ? OR p.package_name LIKE ?)';
                    array_push($params, $term, $term, $term, $term);
                }
                $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
                $stmt = $db->prepare('SELECT o.id, o.client_name, o.client_mobile, o.event_date, o.total_price, o.down_payment_due, o.amount_paid, o.balance_due, o.payment_method, o.payment_status, o.status, o.created_at, p.package_name FROM orders o LEFT JOIN catering_packages p ON p.package_id=o.package_id' . $whereSql . ' ORDER BY o.created_at DESC');
                $stmt->execute($params);
                $data['salesOrders'] = $stmt->fetchAll();
                $summaryStmt = $db->prepare("SELECT COUNT(*) AS order_count, COALESCE(SUM(CASE WHEN o.status IN ('Confirmed','Processing','Completed') THEN o.total_price ELSE 0 END),0) AS booked_total, COALESCE(SUM(o.amount_paid),0) AS collected_total, COALESCE(SUM(CASE WHEN o.status NOT IN ('Cancelled','Rejected') THEN o.balance_due ELSE 0 END),0) AS outstanding_total FROM orders o LEFT JOIN catering_packages p ON p.package_id=o.package_id" . $whereSql);
                $summaryStmt->execute($params);
                $data['salesSummary'] = $summaryStmt->fetch();
                $paymentWhere = ["pay.status='Recorded'"];
                $paymentParams = [];
                if ($filters['from'] !== '') { $paymentWhere[] = 'o.event_date >= ?'; $paymentParams[] = $filters['from']; }
                if ($filters['to'] !== '') { $paymentWhere[] = 'o.event_date <= ?'; $paymentParams[] = $filters['to']; }
                if ($filters['method'] !== '') { $paymentWhere[] = 'pay.payment_method = ?'; $paymentParams[] = $filters['method']; }
                if ($filters['status'] !== '') { $paymentWhere[] = 'o.status = ?'; $paymentParams[] = $filters['status']; }
                if ($filters['package']) { $paymentWhere[] = 'o.package_id = ?'; $paymentParams[] = $filters['package']; }
                if ($filters['q'] !== '') {
                    $term = '%' . $filters['q'] . '%';
                    $paymentWhere[] = '(o.client_name LIKE ? OR o.client_mobile LIKE ? OR CAST(o.id AS CHAR) LIKE ? OR p.package_name LIKE ?)';
                    array_push($paymentParams, $term, $term, $term, $term);
                }
                $paymentSummaryStmt = $db->prepare("SELECT COUNT(*) AS transaction_count, COALESCE(SUM(pay.amount),0) AS collected_total FROM payments pay JOIN orders o ON o.id=pay.order_id LEFT JOIN catering_packages p ON p.package_id=o.package_id WHERE " . implode(' AND ', $paymentWhere));
                $paymentSummaryStmt->execute($paymentParams);
                $data['paymentSummary'] = $paymentSummaryStmt->fetch();
                $paymentStmt = $db->prepare('SELECT pay.payment_id, pay.order_id, pay.amount, pay.payment_method, pay.payment_date, pay.reference_number, o.client_name, p.package_name FROM payments pay JOIN orders o ON o.id=pay.order_id LEFT JOIN catering_packages p ON p.package_id=o.package_id WHERE ' . implode(' AND ', $paymentWhere) . ' ORDER BY pay.payment_date DESC LIMIT 250');
                $paymentStmt->execute($paymentParams);
                $data['salesPayments'] = $paymentStmt->fetchAll();
                $data['salesFilters'] = $filters;
                $data['salesPackages'] = $db->query('SELECT package_id, package_name FROM catering_packages ORDER BY package_name')->fetchAll();
                if (($_GET['export'] ?? '') === 'csv') {
                    header('Content-Type: text/csv; charset=utf-8');
                    header('Content-Disposition: attachment; filename="riz-catering-sales.csv"');
                    $output = fopen('php://output', 'w');
                    fputcsv($output, ['Reservation ID', 'Customer', 'Mobile', 'Package', 'Event date', 'Status', 'Payment status', 'Payment method', 'Total PHP', 'Deposit due PHP', 'Paid PHP', 'Balance PHP', 'Created']);
                    foreach ($data['salesOrders'] as $row) {
                        fputcsv($output, ['RZ-' . $row['id'], rc_csv_text($row['client_name']), rc_csv_text($row['client_mobile']), rc_csv_text($row['package_name']), $row['event_date'], rc_csv_text($row['status']), rc_csv_text($row['payment_status']), rc_csv_text($row['payment_method']), $row['total_price'], $row['down_payment_due'], $row['amount_paid'], $row['balance_due'], $row['created_at']]);
                    }
                    fclose($output);
                    exit;
                }
                rc_render('sales', $data);
                break;
            case 'customers':
                rc_require_owner();
                $data['customerSearch'] = trim((string) ($_GET['q'] ?? ''));
                $customerSql = 'SELECT c.customer_id, c.user_id, c.full_name, c.email, c.contact_number, c.address, c.created_at, COALESCE(o.order_count,0) AS order_count, COALESCE(o.amount_paid,0) AS amount_paid FROM customers c LEFT JOIN (SELECT customer_id, COUNT(*) AS order_count, SUM(amount_paid) AS amount_paid FROM orders GROUP BY customer_id) o ON o.customer_id=c.customer_id';
                $customerParams = [];
                if ($data['customerSearch'] !== '') {
                    $customerSql .= ' WHERE c.full_name LIKE ? OR c.email LIKE ? OR c.contact_number LIKE ? OR CAST(c.customer_id AS CHAR) LIKE ?';
                    $term = '%' . $data['customerSearch'] . '%';
                    array_push($customerParams, $term, $term, $term, $term);
                }
                $customerSql .= ' ORDER BY c.created_at DESC LIMIT 500';
                $customerStmt = $db->prepare($customerSql);
                $customerStmt->execute($customerParams);
                $data['customers'] = $customerStmt->fetchAll();
                rc_render('customers', $data);
                break;
            case 'notifications':
                $user = rc_require_owner();
                $stmt = $db->prepare('SELECT n.*, o.client_name, o.event_date FROM notifications n LEFT JOIN orders o ON o.id=n.order_id WHERE n.user_id=? ORDER BY n.created_at DESC LIMIT 250');
                $stmt->execute([$user['user_id']]);
                $data['ownerNotifications'] = $stmt->fetchAll();
                rc_render('notifications', $data);
                break;
            case 'users':
                rc_require_owner();
                $data['users'] = $db->query("SELECT u.user_id, u.full_name, u.email, u.status, u.created_at FROM users u JOIN customers c ON c.user_id=u.user_id WHERE u.role='Customer' ORDER BY u.created_at DESC")->fetchAll();
                rc_render('users', $data);
                break;
            case 'audit':
                rc_require_owner();
                $data['auditLogs'] = $db->query('SELECT a.*, u.full_name, u.email FROM audit_logs a LEFT JOIN users u ON u.user_id=a.user_id ORDER BY a.created_at DESC LIMIT 250')->fetchAll();
                rc_render('audit', $data);
                break;
            case 'offerings':
                rc_require_owner();
                $data['items'] = $db->query('SELECT * FROM menu_items ORDER BY category, name')->fetchAll();
                $data['packages'] = $db->query('SELECT * FROM catering_packages ORDER BY package_name')->fetchAll();
                $data['packageItems'] = $db->query("SELECT pi.package_id, pi.menu_item_id, pi.additional_charge FROM package_items pi JOIN catering_packages cp ON cp.package_id=pi.package_id JOIN menu_items mi ON mi.id=pi.menu_item_id WHERE cp.package_type<>'buffet' OR (pi.option_group LIKE 'Included: %' AND mi.name=SUBSTRING(pi.option_group,11))")->fetchAll();
                rc_render('offerings', $data);
                break;
            case 'menu-admin':
                rc_require_owner();
                $data['items'] = $db->query('SELECT * FROM menu_items ORDER BY category, name')->fetchAll();
                rc_render('menu-admin', $data);
                break;
            case 'packages-admin':
                rc_require_owner();
                $data['items'] = $db->query('SELECT * FROM menu_items ORDER BY category, name')->fetchAll();
                $data['packages'] = $db->query('SELECT * FROM catering_packages ORDER BY package_name')->fetchAll();
                $data['packageItems'] = $db->query("SELECT pi.package_id, pi.menu_item_id, pi.additional_charge FROM package_items pi JOIN catering_packages cp ON cp.package_id=pi.package_id JOIN menu_items mi ON mi.id=pi.menu_item_id WHERE cp.package_type<>'buffet' OR (pi.option_group LIKE 'Included: %' AND mi.name=SUBSTRING(pi.option_group,11))")->fetchAll();
                rc_render('packages-admin', $data);
                break;
            case 'password':
                rc_require_login();
                rc_render('password', $data);
                break;
            default:
                http_response_code(404);
                rc_render('error', ['title' => 'Page not found', 'message' => 'That page is not available.']);
        }
    } catch (Throwable $error) {
        error_log($error->__toString());
        http_response_code(500);
        rc_render('error', ['title' => 'Something went wrong', 'message' => 'The request could not be completed. Check that the database migration has been applied, then try again.']);
    }
}

function rc_customer_for(array $user): array
{
    $stmt = rc_db()->prepare('SELECT * FROM customers WHERE user_id = ?');
    $stmt->execute([$user['user_id']]);
    $customer = $stmt->fetch();
    if (!$customer) {
        throw new RuntimeException('Customer profile missing.');
    }
    return $customer;
}

function rc_order_for_user(int $id, array $user): array
{
    $stmt = rc_db()->prepare('SELECT o.*, p.package_name FROM orders o LEFT JOIN catering_packages p ON p.package_id=o.package_id WHERE o.id = ?');
    $stmt->execute([$id]);
    $order = $stmt->fetch();
    if (!$order || ($user['role'] === 'Customer' && (int) $order['customer_id'] !== (int) rc_customer_for($user)['customer_id'])) {
        http_response_code(404);
        throw new RuntimeException('Reservation not found.');
    }
    return $order;
}

function rc_create_reminders(int $customerId): void
{
    $stmt = rc_db()->prepare("SELECT o.id, o.event_date FROM orders o WHERE o.customer_id = ? AND o.status = 'Confirmed' AND o.event_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND NOT EXISTS (SELECT 1 FROM notifications n WHERE n.order_id = o.id AND n.notification_type = 'Upcoming Event Reminder')");
    $stmt->execute([$customerId]);
    foreach ($stmt->fetchAll() as $order) {
        rc_notify(null, $customerId, (int) $order['id'], 'Upcoming Event Reminder', 'Reminder: your catering event is scheduled for ' . $order['event_date'] . '.');
    }
}

function rc_claim_guest_order(PDO $db, int $customerId, string $email): bool
{
    $guestOrderId = (int) ($_SESSION['guest_order_id'] ?? 0);
    if ($guestOrderId < 1) return false;
    $stmt = $db->prepare('UPDATE orders SET customer_id=? WHERE id=? AND customer_id IS NULL AND LOWER(client_email)=LOWER(?)');
    $stmt->execute([$customerId, $guestOrderId, $email]);
    if ($stmt->rowCount() === 1) {
        unset($_SESSION['guest_order_id']);
        return true;
    }
    return false;
}

function rc_action(string $action): void
{
    $db = rc_db();
    switch ($action) {
        case 'install-admin':
            $setupKey = (string) getenv('RIZ_OWNER_SETUP_KEY');
            $providedKey = (string) ($_POST['setup_key'] ?? '');
            if ($setupKey === '' || !hash_equals($setupKey, $providedKey)) {
                rc_flash('error', 'Owner setup credentials are invalid or have not been configured.');
                rc_redirect('install');
            }
            $lock = (int) $db->query("SELECT GET_LOCK('riz_catering_first_admin', 5)")->fetchColumn();
            if ($lock !== 1) {
                rc_flash('error', 'Administrator setup is busy. Try again.');
                rc_redirect('install');
            }
            try {
                if (rc_owner_setup_locked($db)) {
                    rc_flash('error', 'The owner account already exists.');
                    rc_redirect('owner-login');
                }
                $name = trim((string) ($_POST['full_name'] ?? ''));
                $email = strtolower(trim((string) ($_POST['email'] ?? '')));
                $password = (string) ($_POST['password'] ?? '');
                if ($name === '' || mb_strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 14) {
                    rc_flash('error', 'Enter a valid name, email, and password of at least 14 characters.');
                    rc_redirect('install');
                }
                $stmt = $db->prepare("INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, 'Admin')");
                $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
                $userId = (int) $db->lastInsertId();
                $setting = $db->prepare("INSERT INTO system_settings (setting_key, setting_value, updated_by) VALUES ('owner_account_created', 'true', ?) ON DUPLICATE KEY UPDATE setting_value='true', updated_by=VALUES(updated_by)");
                $setting->execute([$userId]);
                session_regenerate_id(true);
                $_SESSION['user'] = ['user_id' => $userId, 'full_name' => $name, 'email' => $email, 'role' => 'Admin'];
                $_SESSION['last_activity'] = time();
                $_SESSION['csrf'] = bin2hex(random_bytes(32));
                rc_audit($userId, 'Initial Administrator Created', 'User', (string) $userId);
                rc_redirect('owner');
            } finally {
                $db->query("SELECT RELEASE_LOCK('riz_catering_first_admin')");
            }
        case 'register':
            $name = trim((string) ($_POST['full_name'] ?? ''));
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $phone = trim((string) ($_POST['contact_number'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            if ($name === '' || mb_strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 254 || !preg_match('/^[0-9+() .-]{7,30}$/', $phone) || strlen($password) < 10) {
                rc_flash('error', 'Enter a valid name, email, mobile number, and password (at least 10 characters).');
                rc_redirect('register');
            }
            $db->beginTransaction();
            try {
                $stmt = $db->prepare("INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, 'Customer')");
                $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
                $userId = (int) $db->lastInsertId();
                $stmt = $db->prepare('INSERT INTO customers (user_id, full_name, contact_number, email) VALUES (?, ?, ?, ?)');
                $stmt->execute([$userId, $name, $phone, $email]);
                $guestOrderClaimed = rc_claim_guest_order($db, (int)$db->lastInsertId(), $email);
                $db->commit();
                session_regenerate_id(true);
                $_SESSION['user'] = ['user_id' => $userId, 'full_name' => $name, 'email' => $email, 'role' => 'Customer'];
                $_SESSION['csrf'] = bin2hex(random_bytes(32));
                rc_flash('success', $guestOrderClaimed ? 'Your account is ready and your recent guest booking is now linked for status updates and future feedback.' : 'Your customer account is ready. Book while signed in to receive status notifications and review completed bookings.');
                rc_redirect('dashboard');
            } catch (Throwable $error) {
                $db->rollBack();
                if ($error instanceof PDOException && $error->getCode() === '23000') {
                    rc_flash('error', 'An account with that email already exists.');
                    rc_redirect('register');
                }
                throw $error;
            }
        case 'login':
        case 'owner-login':
            $ownerPortalLogin = $action === 'owner-login';
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $password = (string) ($_POST['password'] ?? '');
            $stmt = $db->prepare("SELECT user_id, full_name, email, password_hash, role, status FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $record = $stmt->fetch();
            if (!$record || $record['status'] !== 'Active' || !in_array($record['role'], ['Customer', 'Admin'], true) || !password_verify($password, $record['password_hash'])) {
                rc_flash('error', 'Email or password is incorrect.');
                rc_redirect($ownerPortalLogin ? 'owner-login' : 'login');
            }
            if ($ownerPortalLogin && $record['role'] !== 'Admin') {
                rc_flash('error', 'This sign-in is only for the owner account.');
                rc_redirect('owner-login');
            }
            if (!$ownerPortalLogin && $record['role'] === 'Admin') {
                rc_flash('error', 'Please use the owner portal to sign in.');
                rc_redirect('owner-login');
            }
            session_regenerate_id(true);
            unset($record['password_hash'], $record['status']);
                $_SESSION['user'] = $record;
                if ($record['role'] === 'Admin') {
                    $_SESSION['last_activity'] = time();
                } else {
                    unset($_SESSION['last_activity']);
                }
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            if ($record['role'] === 'Customer') {
                $customerForClaim = rc_customer_for($record);
                rc_claim_guest_order($db, (int)$customerForClaim['customer_id'], (string)$customerForClaim['email']);
            }
            rc_redirect($record['role'] === 'Admin' ? 'owner' : 'dashboard');
        case 'logout':
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
            session_destroy();
            session_start();
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
            rc_flash('success', 'You have signed out.');
            rc_redirect('home');
        case 'profile':
            $user = rc_require_login();
            $customer = rc_customer_for($user);
            $name = trim((string) ($_POST['full_name'] ?? ''));
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $phone = trim((string) ($_POST['contact_number'] ?? ''));
            $address = trim((string) ($_POST['address'] ?? ''));
            if ($name === '' || mb_strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[0-9+() .-]{7,30}$/', $phone) || mb_strlen($address) > 500) {
                rc_flash('error', 'Check the name, email, mobile number, and address fields.');
                rc_redirect('dashboard');
            }
            $db->beginTransaction();
            try {
                $db->prepare('UPDATE users SET full_name=?, email=? WHERE user_id=?')->execute([$name, $email, $user['user_id']]);
                $db->prepare('UPDATE customers SET full_name=?, email=?, contact_number=?, address=? WHERE customer_id=?')->execute([$name, $email, $phone, $address, $customer['customer_id']]);
                $db->commit();
            } catch (Throwable $error) {
                $db->rollBack();
                rc_flash('error', 'The email may already be in use. Your account was not changed.');
                rc_redirect('dashboard');
            }
            $_SESSION['user']['full_name'] = $name;
            $_SESSION['user']['email'] = $email;
            rc_flash('success', 'Your account details have been updated.');
            rc_redirect('dashboard');
        case 'password':
            $user = rc_require_login();
            $current = (string) ($_POST['current_password'] ?? '');
            $new = (string) ($_POST['new_password'] ?? '');
            $confirm = (string) ($_POST['confirm_password'] ?? '');
            $stmt = $db->prepare('SELECT password_hash FROM users WHERE user_id=?');
            $stmt->execute([$user['user_id']]);
            $hash = $stmt->fetchColumn();
            if (!$hash || !password_verify($current, $hash) || strlen($new) < 10 || $new !== $confirm) {
                rc_flash('error', 'Verify your current password and enter matching new passwords with at least 10 characters.');
                rc_redirect('dashboard');
            }
            $db->prepare('UPDATE users SET password_hash=? WHERE user_id=?')->execute([password_hash($new, PASSWORD_DEFAULT), $user['user_id']]);
            rc_audit((int) $user['user_id'], 'Password Changed', 'User', (string) $user['user_id']);
            rc_flash('success', 'Password updated.');
            rc_redirect('dashboard');
        case 'reserve':
            $user = rc_user();
            if (($user['role'] ?? '') === 'Admin') rc_redirect('owner');
            if (($user['role'] ?? '') === 'Customer') {
                $user = rc_require_login();
                $customer = rc_customer_for($user);
                $clientName = $customer['full_name']; $clientEmail = $customer['email']; $clientMobile = $customer['contact_number'];
            } else {
                $customer = null;
                $clientName = trim((string)($_POST['client_name'] ?? ''));
                $clientEmail = strtolower(trim((string)($_POST['client_email'] ?? '')));
                $clientMobile = trim((string)($_POST['client_mobile'] ?? ''));
            }
            $orderType = (string) ($_POST['order_type'] ?? 'custom');
            if (!in_array($orderType, ['alacarte', 'packed_meal', 'buffet', 'custom'], true)) $orderType = 'custom';
            $packageId = filter_var($_POST['package_id'] ?? null, FILTER_VALIDATE_INT);
            $guests = filter_var($_POST['guest_count'] ?? null, FILTER_VALIDATE_INT);
            $date = trim((string) ($_POST['event_date'] ?? ''));
            $start = trim((string) ($_POST['event_start_time'] ?? ''));
            $end = trim((string) ($_POST['event_end_time'] ?? ''));
            $dining = trim((string) ($_POST['dining_time'] ?? ''));
            $venue = trim((string) ($_POST['venue'] ?? ''));
            $occasion = trim((string) ($_POST['occasion'] ?? ''));
            $motif = trim((string) ($_POST['motif'] ?? ''));
            $requests = trim((string) ($_POST['special_requests'] ?? ''));
            if (empty($_POST['accept_cancellation_policy'])) {
                rc_flash('error', 'Read and accept the cancellation policy before submitting your reservation.');
                rc_redirect('reservation');
            }
            if (!$guests || $guests < 1 || $guests > 10000 || !rc_valid_date($date) || $date < date('Y-m-d') || !rc_valid_time($start) || !rc_valid_time($end) || !rc_valid_time($dining) || $end <= $start || $dining < $start || $dining > $end || $venue === '' || mb_strlen($venue) > 500 || mb_strlen($occasion) > 100 || mb_strlen($motif) > 160 || mb_strlen($requests) > 5000 || $clientName === '' || mb_strlen($clientName)>120 || !filter_var($clientEmail,FILTER_VALIDATE_EMAIL) || mb_strlen($clientEmail)>254 || !preg_match('/^[0-9+() .-]{7,30}$/',$clientMobile)) {
                rc_flash('error', 'Complete the guest count, event date/time, and venue with valid values.');
                rc_redirect('reservation');
            }
            $package = null; $selectedByGroup = []; $optionCharges = []; $chargeTypes = []; $optionRows = [];
            if ($orderType === 'alacarte') {
                $quantities = $_POST['items'] ?? [];
                if (!is_array($quantities)) $quantities = [];
                $itemIds = [];
                foreach ($quantities as $rawId => $rawQuantity) {
                    $itemId = filter_var($rawId, FILTER_VALIDATE_INT);
                    $quantity = filter_var($rawQuantity, FILTER_VALIDATE_INT);
                    if (!$itemId || $quantity === false || $quantity < 0 || $quantity > 1000) { rc_flash('error', 'Enter valid non-negative whole-number quantities for the dishes.'); rc_redirect('reservation'); }
                    if ($quantity > 0) $itemIds[] = (int)$itemId;
                }
                if (!$itemIds) { rc_flash('error', 'Select at least one dish for your a la carte order.'); rc_redirect('reservation'); }
                $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
                $stmt = $db->prepare("SELECT id,name,price,unit_type FROM menu_items WHERE is_active=1 AND id IN ($placeholders)"); $stmt->execute($itemIds);
                $availableItems = []; foreach ($stmt->fetchAll() as $row) $availableItems[(int)$row['id']] = $row;
                $totalCents = 0;
                foreach ($itemIds as $itemId) {
                    $qty = filter_var($quantities[$itemId] ?? null, FILTER_VALIDATE_INT);
                    if (!$qty || $qty < 1 || $qty > 1000 || !isset($availableItems[$itemId])) { rc_flash('error', 'Enter a valid quantity for each active menu item.'); rc_redirect('reservation'); }
                    $row = $availableItems[$itemId]; $line = rc_cents($row['price']) * $qty;
                    if ($line > 9999999999 - $totalCents) { rc_flash('error', 'The order total is too large.'); rc_redirect('reservation'); }
                    $totalCents += $line;
                    $lineDetails = $row['unit_type'] === 'tray_25_35pax'
                        ? $qty . ' tray(s) · serves approximately ' . (25 * $qty) . '–' . (35 * $qty) . ' people'
                        : $qty . ' × ' . str_replace('_', ' ', (string) $row['unit_type']);
                    $optionRows[] = [$itemId, $row['name'], $row['price'], 0, $lineDetails, $qty];
                }
                $diningTier = 'A La Carte'; $packageId = null;
            } else {
                if (!$packageId) { rc_flash('error', 'Choose a food package.'); rc_redirect('reservation'); }
                $stmt = $db->prepare("SELECT * FROM catering_packages WHERE package_id=? AND status='Active'"); $stmt->execute([$packageId]); $package = $stmt->fetch();
                if (!$package || ($orderType !== 'custom' && $package['package_type'] !== $orderType) || ($orderType === 'custom' && $package['package_type'] !== 'custom') || $guests < (int)$package['minimum_guests'] || (($orderType === 'packed_meal' || $orderType === 'buffet') && $guests < 50)) { rc_flash('error', 'Choose an active package of the selected type and meet its minimum guest count.'); rc_redirect('reservation'); }
                if (!empty($package['maximum_guests']) && $guests > (int)$package['maximum_guests']) { rc_flash('error', 'The selected package maximum is ' . (int)$package['maximum_guests'] . ' guests.'); rc_redirect('reservation'); }
                $groupsStmt = $db->prepare("SELECT option_group,component_type,selection_rule,menu_item_id,additional_charge,charge_type FROM package_items WHERE package_id=? AND (? <> 'packed_meal' OR component_type <> 'rice')"); $groupsStmt->execute([$packageId, $orderType]);
                $allowed = []; $rules = []; $componentByItem = [];
                foreach ($groupsStmt->fetchAll() as $option) { $allowed[$option['option_group']][] = (int)$option['menu_item_id']; $rules[$option['option_group']] = $option['selection_rule']; $optionCharges[(int)$option['menu_item_id']] = rc_cents($option['additional_charge']); $chargeTypes[(int)$option['menu_item_id']] = $option['charge_type']; $componentByItem[(int)$option['menu_item_id']] = $option['component_type']; }
                $selected = $_POST['options'] ?? []; if (!is_array($selected)) $selected = [];
                foreach ($selected as $group => $ids) { if (!is_array($ids)) $ids = [$ids]; foreach ($ids as $id) { $id = filter_var($id, FILTER_VALIDATE_INT); if (!$id || !in_array((int)$id, $allowed[$group] ?? [], true)) { rc_flash('error', 'A selected food option is not available in this package.'); rc_redirect('reservation'); } if (in_array((int)$id, $selectedByGroup[$group] ?? [], true)) { rc_flash('error', 'A food choice cannot be selected twice.'); rc_redirect('reservation'); } $selectedByGroup[$group][] = (int)$id; } }
                if ($orderType === 'packed_meal') {
                    $riceStmt = $db->query("SELECT id FROM menu_items WHERE is_active=1 AND component_type='rice' AND name='Steamed Rice' ORDER BY id LIMIT 1");
                    $riceId = (int) $riceStmt->fetchColumn();
                    if ($riceId < 1) { rc_flash('error', 'Steamed Rice is not available. Please contact the owner.'); rc_redirect('reservation'); }
                    $selectedByGroup['rice'] = [$riceId];
                    $componentByItem[$riceId] = 'rice';
                    $optionCharges[$riceId] = 0;
                    $chargeTypes[$riceId] = 'per_event';
                }
                foreach ($rules as $group => $rule) { $count = count($selectedByGroup[$group] ?? []); if (($rule === 'Required One' && $count !== 1) || ($rule === 'Optional Many' && $count > count($allowed[$group]))) { rc_flash('error', 'Choose one dish in each required package section.'); rc_redirect('reservation'); } }
                $ruleStmt=$db->prepare('SELECT component_type,minimum_quantity,maximum_quantity FROM package_component_rules WHERE package_id=?'); $ruleStmt->execute([$packageId]);
                $counts=[]; foreach($selectedByGroup as $ids) foreach($ids as $itemId) $counts[$componentByItem[$itemId] ?? 'other']=($counts[$componentByItem[$itemId] ?? 'other'] ?? 0)+1;
                foreach($ruleStmt->fetchAll() as $rule) { $n=$counts[$rule['component_type']] ?? 0; if($n < (int)$rule['minimum_quantity'] || $n > (int)$rule['maximum_quantity']) { rc_flash('error', 'The selected dishes do not meet the package component requirements.'); rc_redirect('reservation'); } }
                $unitCents=rc_cents($package['package_price']); $totalCents=$package['price_type']==='Per Person' ? $unitCents*$guests : $unitCents;
                $stmt=$db->prepare('SELECT id,name,price FROM menu_items WHERE id=? AND is_active=1');
                foreach($selectedByGroup as $group=>$ids) foreach($ids as $itemId) { $stmt->execute([$itemId]); $item=$stmt->fetch(); if(!$item) throw new RuntimeException('A selected menu option was deactivated.'); $charge=$optionCharges[$itemId] ?? 0; $lineCharge=($chargeTypes[$itemId] ?? 'per_event')==='per_person' ? $charge*$guests : $charge; if($lineCharge > 9999999999-$totalCents) throw new RuntimeException('The reservation total is too large.'); $totalCents+=$lineCharge; $optionRows[]=[$itemId,$item['name'],$item['price'],$charge,$group,1]; }
                $diningTier = $package['set_name'] ?: $package['package_name'];
            }
            if ($totalCents < 1 || $totalCents > 9999999999) {
                rc_flash('error', 'The package price is not configured. Ask the owner to update the menu.');
                rc_redirect('reservation');
            }
            $stmt = $db->prepare("SELECT COUNT(*) FROM events WHERE event_date=? AND schedule_status='Confirmed' AND start_time < ? AND end_time > ?");
            $stmt->execute([$date, $end . ':00', $start . ':00']);
            if ((int) $stmt->fetchColumn() > 0) {
                rc_flash('error', 'That event time conflicts with a confirmed booking. Choose a different time.');
                rc_redirect('reservation');
            }
            $db->beginTransaction();
            $deposit = intdiv($totalCents + 1, 2);
            $stmt = $db->prepare("INSERT INTO orders (customer_id, package_id, order_type, client_name, client_email, client_mobile, event_date, event_start_time, event_end_time, dining_time, delivery_address, guest_count, occasion, motif, special_requests, dining_tier, total_price, down_payment_due, amount_paid, balance_due, payment_method, payment_status, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '0.00', ?, 'Cash', 'Pending', 'Pending')");
            $stmt->execute([
                $customer['customer_id'] ?? null, $packageId, $orderType, $clientName, $clientEmail, $clientMobile, $date,
                $start . ':00', $end . ':00', $dining . ':00', $venue, $guests,
                 $occasion ?: null, $motif ?: null,
                 $requests ?: null, $diningTier, rc_money_db($totalCents), rc_money_db($deposit), rc_money_db($totalCents),
            ]);
            $orderId = (int) $db->lastInsertId();
            $lineInsert = $db->prepare('INSERT INTO order_items (order_id, menu_item_id, item_name_snapshot, unit_price_snapshot, customization_charge, customization_details, quantity) VALUES (?, ?, ?, ?, ?, ?, ?)');
            foreach ($optionRows as [$itemId,$itemName,$itemPrice,$charge,$details,$quantity]) $lineInsert->execute([$orderId,$itemId,$itemName,$itemPrice,rc_money_db($charge),$details,$quantity]);
            $db->prepare("INSERT INTO events (order_id, event_date, start_time, end_time, venue, schedule_status) VALUES (?, ?, ?, ?, ?, 'Tentative')")->execute([$orderId, $date, $start . ':00', $end . ':00', $venue]);
            if ($customer) rc_notify((int) $user['user_id'], (int) $customer['customer_id'], $orderId, 'Reservation Submitted', 'Reservation #' . $orderId . ' is pending owner review. A 50% deposit of ' . rc_money($deposit) . ' is required before confirmation.');
            rc_notify_owner($orderId, 'New Reservation', 'New reservation #' . $orderId . ' from ' . $clientName . ' needs review.');
            rc_audit($user ? (int)$user['user_id'] : null, 'Reservation Submitted', 'Reservation', (string) $orderId, null, ['order_type'=>$orderType, 'package_id' => $packageId, 'total' => rc_money_db($totalCents)]);
            $db->commit();
            if ($customer) {
                rc_flash('success', 'Reservation submitted. Track its status and notifications from your dashboard.');
                rc_redirect('reservation-view', ['id' => $orderId]);
            }
            $_SESSION['guest_order_id'] = $orderId;
            rc_redirect('guest-confirmation');
        case 'mark-read':
            $user = rc_require_login();
            if ($user['role'] !== 'Customer') {
                rc_flash('error', 'Customer notifications are only available to customer accounts.');
                rc_redirect('home');
            }
            $customer = rc_customer_for($user);
            $stmt = $db->prepare('UPDATE notifications SET read_at=NOW() WHERE customer_id=? AND read_at IS NULL');
            $stmt->execute([$customer['customer_id']]);
            rc_redirect('dashboard');
        case 'mark-owner-read':
            $user = rc_require_owner();
            $stmt = $db->prepare('UPDATE notifications SET read_at=NOW() WHERE user_id=? AND read_at IS NULL');
            $stmt->execute([$user['user_id']]);
            rc_flash('success', 'Owner notifications marked as read.');
            rc_redirect('owner');
        case 'mark-owner-notification':
            $user = rc_require_owner();
            $notificationId = filter_var($_POST['notification_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$notificationId) {
                rc_flash('error', 'Select a valid notification.');
                rc_redirect('notifications');
            }
            $stmt = $db->prepare('UPDATE notifications SET read_at=NOW() WHERE notification_id=? AND user_id=? AND read_at IS NULL');
            $stmt->execute([$notificationId, $user['user_id']]);
            rc_flash('success', 'Notification marked as read.');
            rc_redirect('notifications');
        case 'owner-customer-profile':
            $user = rc_require_owner();
            $customerId = filter_var($_POST['customer_id'] ?? null, FILTER_VALIDATE_INT);
            $name = trim((string) ($_POST['full_name'] ?? ''));
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $phone = trim((string) ($_POST['contact_number'] ?? ''));
            $address = trim((string) ($_POST['address'] ?? ''));
            if (!$customerId || $name === '' || mb_strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 254 || !preg_match('/^[0-9+() .-]{7,30}$/', $phone) || mb_strlen($address) > 500) {
                rc_flash('error', 'Check the customer name, email, mobile number, and address.');
                rc_redirect('customers');
            }
            $stmt = $db->prepare('SELECT * FROM customers WHERE customer_id=?');
            $stmt->execute([$customerId]);
            $oldCustomer = $stmt->fetch();
            if (!$oldCustomer) {
                rc_flash('error', 'Customer record not found.');
                rc_redirect('customers');
            }
            $db->beginTransaction();
            try {
                $db->prepare('UPDATE customers SET full_name=?, email=?, contact_number=?, address=? WHERE customer_id=?')->execute([$name, $email, $phone, $address ?: null, $customerId]);
                if ($oldCustomer['user_id']) {
                    $db->prepare('UPDATE users SET full_name=?, email=? WHERE user_id=?')->execute([$name, $email, $oldCustomer['user_id']]);
                }
                rc_audit((int) $user['user_id'], 'Customer Profile Updated', 'Customer', (string) $customerId, $oldCustomer, ['full_name' => $name, 'email' => $email, 'contact_number' => $phone, 'address' => $address]);
                $db->commit();
            } catch (PDOException $error) {
                if ($db->inTransaction()) $db->rollBack();
                if ($error->getCode() === '23000') {
                    rc_flash('error', 'That email address is already used by another account.');
                    rc_redirect('customers');
                }
                throw $error;
            }
            rc_flash('success', 'Customer contact record updated. Existing reservations retain their saved details.');
            rc_redirect('customers');
        case 'status':
            $user = rc_require_owner();
            $id = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
            $next = (string) ($_POST['status'] ?? '');
            $transitions = ['Pending' => ['Confirmed', 'Rejected', 'Cancelled'], 'Confirmed' => ['Processing', 'Cancelled'], 'Processing' => ['Completed', 'Cancelled']];
            $stmt = $db->prepare('SELECT * FROM orders WHERE id=? FOR UPDATE');
            $db->beginTransaction();
            $stmt->execute([$id]);
            $order = $stmt->fetch();
            if (!$order || !in_array($next, $transitions[$order['status']] ?? [], true)) {
                $db->rollBack();
                rc_flash('error', 'That reservation status transition is not allowed.');
                rc_redirect($user['role'] === 'Admin' ? 'orders' : 'owner');
            }
            if ($next === 'Confirmed') {
                if (rc_cents($order['amount_paid']) < rc_cents($order['down_payment_due'])) {
                    $db->rollBack();
                    rc_flash('error', 'Record the required 50% down payment before confirmation.');
                    rc_redirect($user['role'] === 'Admin' ? 'orders' : 'owner');
                }
                $stmt = $db->prepare("SELECT COUNT(*) FROM events WHERE event_date=? AND schedule_status='Confirmed' AND order_id<>? AND start_time < ? AND end_time > ?");
                $stmt->execute([$order['event_date'], $id, $order['event_end_time'], $order['event_start_time']]);
                if ((int) $stmt->fetchColumn() > 0) {
                    $db->rollBack();
                    rc_flash('error', 'Another confirmed event overlaps this time.');
                    rc_redirect($user['role'] === 'Admin' ? 'orders' : 'owner');
                }
            }
            if ($next === 'Rejected' && (rc_cents($order['amount_paid']) > 0 || $order['status'] !== 'Pending')) {
                $db->rollBack();
                rc_flash('error', 'Only unpaid pending reservations can be rejected. Use cancellation and payment records for other cases.');
                rc_redirect('orders');
            }
            if ($next === 'Rejected' && $user['role'] !== 'Admin') {
                $db->rollBack();
                rc_flash('error', 'Only the owner can reject a reservation.');
                rc_redirect('owner');
            }
            if ($next === 'Completed' && rc_cents($order['amount_paid']) < rc_cents($order['total_price'])) {
                $db->rollBack();
                rc_flash('error', 'Record the remaining balance before marking this reservation completed.');
                rc_redirect($user['role'] === 'Admin' ? 'orders' : 'owner');
            }
            if ($next === 'Completed' && ($order['delivery_status'] ?? 'Not Started') !== 'Delivered') {
                $db->rollBack();
                rc_flash('error', 'Update food progress to Delivered before completing this reservation.');
                rc_redirect($user['role'] === 'Admin' ? 'orders' : 'owner');
            }
            if ($next === 'Completed') {
                $eventEnd = new DateTimeImmutable($order['event_date'] . ' ' . $order['event_end_time']);
                if ($eventEnd > new DateTimeImmutable('now')) {
                    $db->rollBack();
                    rc_flash('error', 'A reservation can be completed only after its scheduled event has ended.');
                    rc_redirect($user['role'] === 'Admin' ? 'orders' : 'owner');
                }
            }
            $eventStatus = match ($next) { 'Confirmed' => 'Confirmed', 'Completed' => 'Completed', 'Cancelled' => 'Cancelled', 'Rejected' => 'Rejected', default => 'Tentative' };
            if ($next === 'Cancelled') {
                $reason = trim((string) ($_POST['cancellation_reason'] ?? ''));
                if (mb_strlen($reason) < 5 || mb_strlen($reason) > 1000) {
                    $db->rollBack();
                    rc_flash('error', 'Enter a cancellation reason between 5 and 1,000 characters.');
                    rc_redirect($user['role'] === 'Admin' ? 'orders' : 'owner');
                }
                // The 10% cancellation fee is included within the non-refundable 50% deposit.
                // Any amount paid above the deposit is due back; never calculate a negative refund.
                $deposit = rc_cents($order['down_payment_due']);
                $paid = rc_cents($order['amount_paid']);
                $fee = min((int) round(rc_cents($order['total_price']) * 0.10), $paid);
                $refundDue = max(0, $paid - $deposit);
                $db->prepare('UPDATE orders SET status=?, cancellation_reason=?, cancellation_fee=?, refund_due=?, cancelled_at=NOW(), cancelled_by=? WHERE id=?')->execute([$next, $reason, rc_money_db($fee), rc_money_db($refundDue), $user['user_id'], $id]);
                $statusMessageExtra = ' Cancellation policy: the 10% fee (' . rc_money($fee) . ') is included in the non-refundable 50% deposit. Refund due: ' . rc_money($refundDue) . ', to be returned manually outside this system.';
            } elseif ($next === 'Rejected') {
                $reason = trim((string) ($_POST['rejection_reason'] ?? ''));
                if (mb_strlen($reason) < 5 || mb_strlen($reason) > 1000) {
                    $db->rollBack();
                    rc_flash('error', 'Enter a rejection reason between 5 and 1,000 characters.');
                    rc_redirect('orders');
                }
                $db->prepare('UPDATE orders SET status=?, rejected_reason=?, rejected_at=NOW(), rejected_by=? WHERE id=?')->execute([$next, $reason, $user['user_id'], $id]);
            } else {
                $db->prepare('UPDATE orders SET status=? WHERE id=?')->execute([$next, $id]);
            }
            $db->prepare('UPDATE events SET schedule_status=? WHERE order_id=?')->execute([$eventStatus, $id]);
            $statusMessage = 'Reservation #' . $id . ' is now ' . $next . '.';
            if ($next === 'Rejected') $statusMessage .= ' Reason: ' . $reason;
            if ($next === 'Cancelled') $statusMessage .= $statusMessageExtra ?? '';
            rc_notify(null, $order['customer_id'] ? (int) $order['customer_id'] : null, (int) $id, 'Reservation Status', $statusMessage);
            $newStatus = ['status' => $next];
            if ($next === 'Cancelled') $newStatus['cancellation_reason'] = $reason;
            if ($next === 'Rejected') $newStatus['rejected_reason'] = $reason;
            rc_audit((int) $user['user_id'], 'Reservation Status Updated', 'Reservation', (string) $id, ['status' => $order['status']], $newStatus);
            $db->commit();
            rc_flash('success', 'Reservation status updated.');
            rc_redirect($user['role'] === 'Admin' ? 'orders' : 'owner');
        case 'update-item':
            $user = rc_require_owner();
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            $name = trim((string) ($_POST['name'] ?? ''));
            $category = trim((string) ($_POST['category'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $price = trim((string) ($_POST['price'] ?? ''));
            $unitType = (string) ($_POST['unit_type'] ?? 'tray_25_35pax');
            $componentType = (string) ($_POST['component_type'] ?? 'alacarte');
            $imageUrl = trim((string) ($_POST['image_url'] ?? ''));
            $stmt = $db->prepare('SELECT * FROM menu_items WHERE id=?');
            $stmt->execute([$id]);
            $old = $stmt->fetch();
            if (!$old || $name === '' || mb_strlen($name) > 255 || $category === '' || mb_strlen($category) > 50 || mb_strlen($description) > 5000 || mb_strlen($imageUrl) > 1000 || ($imageUrl !== '' && !preg_match('~^(https?://|/)[^\s<>"\']+$~i', $imageUrl)) || !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $price)) {
                rc_flash('error', 'Enter valid menu item details and price.');
                rc_redirect('menu-admin');
            }
            $imageUrl = rc_uploaded_menu_image() ?? (isset($_POST['remove_image']) ? null : ($imageUrl ?: ($old['image_url'] ?? null)));
            if (!in_array($unitType, ['tray_25_35pax','per_pc','per_box','per_tray','per_layer','per_slice','per_person','each'], true) || !in_array($componentType, ['main_dish','side_dish','dessert','salad','rice','drink','alacarte'], true)) { rc_flash('error', 'Choose a valid serving unit and dish role.'); rc_redirect('menu-admin'); }
            $db->prepare('UPDATE menu_items SET name=?, category=?, description=?, image_url=?, price=?, unit_type=?, component_type=?, is_vegan=?, is_gluten_free=? WHERE id=?')->execute([$name, $category, $description, $imageUrl ?: null, $price, $unitType, $componentType, isset($_POST['is_vegan']) ? 1 : 0, isset($_POST['is_gluten_free']) ? 1 : 0, $id]);
            if ($old['name'] !== $name) {
                $defaultGroups = $db->prepare("SELECT DISTINCT pi.package_id, pi.option_group FROM package_items pi JOIN catering_packages cp ON cp.package_id=pi.package_id WHERE pi.menu_item_id=? AND cp.package_type='buffet' AND pi.option_group=CONCAT('Included: ',?)");
                $defaultGroups->execute([$id, $old['name']]);
                $renameGroup = $db->prepare('UPDATE package_items SET option_group=? WHERE package_id=? AND option_group=?');
                foreach ($defaultGroups->fetchAll() as $defaultGroup) $renameGroup->execute(['Included: ' . $name, $defaultGroup['package_id'], $defaultGroup['option_group']]);
            }
            rc_audit((int) $user['user_id'], 'Menu Item Updated', 'Menu Item', (string) $id, $old, ['name' => $name, 'category' => $category, 'price' => $price, 'image_url' => $imageUrl]);
            rc_flash('success', 'Menu item updated. Existing reservations retain their price snapshots.');
            rc_redirect('menu-admin');
        case 'update-package':
            $user = rc_require_owner();
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            $name = trim((string) ($_POST['package_name'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $price = trim((string) ($_POST['package_price'] ?? ''));
            $priceType = (string) ($_POST['price_type'] ?? 'Fixed');
            $packageType = (string) ($_POST['package_type'] ?? 'custom');
            $setName = trim((string) ($_POST['set_name'] ?? ''));
            $minimum = filter_var($_POST['minimum_guests'] ?? null, FILTER_VALIDATE_INT);
            $maximum = trim((string) ($_POST['maximum_guests'] ?? '')) === '' ? null : filter_var($_POST['maximum_guests'], FILTER_VALIDATE_INT);
            $imageUrl = trim((string) ($_POST['image_url'] ?? ''));
            $selected = $_POST['menu_items'] ?? [];
            $optionCharges = $_POST['option_charges'] ?? [];
            $stmt = $db->prepare('SELECT * FROM catering_packages WHERE package_id=?');
            $stmt->execute([$id]);
            $old = $stmt->fetch();
            if (!$old || $name === '' || mb_strlen($name) > 160 || mb_strlen($description) > 5000 || mb_strlen($imageUrl) > 1000 || mb_strlen($setName)>80 || !in_array($packageType,['custom','packed_meal','buffet'],true) || ($imageUrl !== '' && !preg_match('~^(https?://|/)[^\s<>"\']+$~i', $imageUrl)) || !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $price) || !in_array($priceType, ['Fixed', 'Per Person'], true) || !$minimum || $minimum < 1 || (($packageType==='packed_meal'||$packageType==='buffet') && ($minimum<50 || $priceType!=='Per Person')) || ($maximum !== null && ($maximum === false || $maximum < $minimum)) || !is_array($selected) || !is_array($optionCharges)) {
                rc_flash('error', 'Enter valid package details, price, and minimum guests.');
                rc_redirect('packages-admin');
            }
            if (count($selected) < 1) {
                rc_flash('error', 'Each package needs at least one permitted food choice.');
                rc_redirect('packages-admin');
            }
            $imageUrl = rc_uploaded_menu_image() ?? (isset($_POST['remove_image']) ? null : ($imageUrl ?: ($old['image_url'] ?? null)));
            $db->beginTransaction();
            $db->prepare('UPDATE catering_packages SET package_name=?, package_type=?, set_name=?, description=?, image_url=?, price_type=?, package_price=?, minimum_guests=?, maximum_guests=? WHERE package_id=?')->execute([$name, $packageType, $setName ?: null, $description, $imageUrl ?: null, $priceType, $price, $minimum, $maximum, $id]);
            $db->prepare('DELETE FROM package_items WHERE package_id=?')->execute([$id]);
            $db->prepare('DELETE FROM package_component_rules WHERE package_id=?')->execute([$id]);
            $insert = $db->prepare("INSERT INTO package_items (package_id, menu_item_id, option_group, component_type, selection_rule, additional_charge, charge_type) SELECT ?, id, IF(?='packed_meal',IF(category='Add-ons','add_on',component_type),IF(?='buffet',CONCAT('Included: ',name),CONCAT(COALESCE(NULLIF(category, ''), 'Dish'), ' · ', name))), CASE WHEN category='Add-ons' THEN 'add_on' WHEN component_type IN ('main_dish','side_dish','dessert','salad','rice','drink') THEN component_type ELSE 'other' END, IF(?='packed_meal' AND (category='Add-ons' OR (?='Tier D' AND component_type='main_dish')),'Optional Many','Required One'), ?, IF(?='packed_meal' AND category='Add-ons','per_person','per_event') FROM menu_items WHERE id=? AND is_active=1");
            foreach (array_unique(array_map('intval', $selected)) as $itemId) {
                $charge = trim((string) ($optionCharges[$itemId] ?? '0'));
                if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $charge)) {
                    $db->rollBack();
                    rc_flash('error', 'Enter a valid non-negative surcharge for each selected menu choice.');
                    rc_redirect('packages-admin');
                }
                $insert->execute([$id, $packageType, $packageType, $packageType, $setName, $charge, $packageType, $itemId]);
                if ($insert->rowCount() !== 1) {
                    $db->rollBack();
                    rc_flash('error', 'A selected menu choice is missing or inactive. Refresh the package form and try again.');
                    rc_redirect('packages-admin');
                }
            }
            if ($packageType === 'buffet') {
                $fillBuffetOptions = $db->prepare("INSERT INTO package_items (package_id,menu_item_id,option_group,component_type,selection_rule,additional_charge,charge_type) SELECT DISTINCT base.package_id,alternative.id,base.option_group,base.component_type,'Required One',0,'per_person' FROM package_items base JOIN menu_items alternative ON alternative.component_type=base.component_type AND alternative.is_active=1 WHERE base.package_id=? AND base.option_group LIKE 'Included: %' AND NOT EXISTS (SELECT 1 FROM package_items existing WHERE existing.package_id=base.package_id AND existing.menu_item_id=alternative.id AND existing.option_group=base.option_group)");
                $fillBuffetOptions->execute([$id]);
            }
            if ($packageType === 'packed_meal') {
                $componentCounts = ['main_dish'=>0,'side_dish'=>0,'rice'=>0,'dessert'=>0,'salad'=>0];
                $componentQuery = $db->prepare("SELECT component_type,COUNT(*) AS item_count FROM menu_items WHERE is_active=1 AND id IN (" . implode(',', array_fill(0, count(array_unique(array_map('intval',$selected))), '?')) . ") GROUP BY component_type");
                $componentQuery->execute(array_values(array_unique(array_map('intval',$selected))));
                foreach ($componentQuery->fetchAll() as $component) $componentCounts[$component['component_type']] = (int)$component['item_count'];
                $required = ['main_dish'=>1,'side_dish'=>1,'rice'=>1,'dessert'=>0,'salad'=>0];
                if ($setName === 'Tier B') $required['dessert']=1;
                if ($setName === 'Tier C') { $required['dessert']=1; $required['salad']=1; }
                if ($setName === 'Tier D') { $required['main_dish']=2; $required['dessert']=1; $required['salad']=1; }
                foreach ($required as $component=>$minimumCount) {
                    if (($componentCounts[$component] ?? 0) < $minimumCount) { $db->rollBack(); rc_flash('error', 'This packed meal tier needs at least ' . $minimumCount . ' permitted ' . str_replace('_',' ',$component) . ' choice(s).'); rc_redirect('packages-admin'); }
                }
                $ruleInsert=$db->prepare('INSERT INTO package_component_rules (package_id,component_type,minimum_quantity,maximum_quantity) VALUES (?,?,?,?)');
                foreach ($required as $component=>$minimumCount) $ruleInsert->execute([$id,$component,$minimumCount,$minimumCount]);
                $ruleInsert->execute([$id,'add_on',0,20]);
            }
            rc_audit((int) $user['user_id'], 'Package Updated', 'Package', (string) $id, $old, ['name' => $name, 'price_type' => $priceType, 'price' => $price, 'minimum_guests' => $minimum, 'maximum_guests' => $maximum, 'image_url' => $imageUrl]);
            $db->commit();
            rc_flash('success', 'Package updated. Existing reservations keep their stored totals.');
            rc_redirect('packages-admin');
        case 'payment':
            $user = rc_require_owner();
            $id = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
            $amount = trim((string) ($_POST['amount'] ?? ''));
            $amountCents = rc_cents($amount);
            $method = (string) ($_POST['payment_method'] ?? '');
            if ($amountCents < 1 || !in_array($method, ['Cash', 'GCash', 'Bank Transfer', 'Check'], true)) {
                rc_flash('error', 'Enter a valid payment amount and an approved method.');
                rc_redirect($user['role'] === 'Admin' ? 'orders' : 'owner');
            }
            $db->beginTransaction();
            $stmt = $db->prepare('SELECT * FROM orders WHERE id=? FOR UPDATE');
            $stmt->execute([$id]);
            $order = $stmt->fetch();
            if (!$order || !in_array($order['status'], ['Pending', 'Confirmed', 'Processing'], true)) throw new RuntimeException('Payment cannot be recorded for this reservation.');
            $paid = rc_cents($order['amount_paid']);
            $total = rc_cents($order['total_price']);
            if ($amountCents > $total - $paid) throw new RuntimeException('Payment cannot exceed the remaining balance.');
            $newPaid = $paid + $amountCents;
            $balance = $total - $newPaid;
            $paymentStatus = $balance === 0 ? 'Paid in Full' : 'Partially Paid';
            $db->prepare("INSERT INTO payments (order_id, amount, payment_method, payment_date, reference_number, recorded_by) VALUES (?, ?, ?, NOW(), ?, ?)")->execute([$id, rc_money_db($amountCents), $method, trim((string) ($_POST['reference_number'] ?? '')) ?: null, $user['user_id']]);
            $db->prepare('UPDATE orders SET amount_paid=?, balance_due=?, payment_status=?, payment_method=? WHERE id=?')->execute([rc_money_db($newPaid), rc_money_db($balance), $paymentStatus, $method, $id]);
            rc_notify(null, $order['customer_id'] ? (int) $order['customer_id'] : null, (int) $id, 'Payment Recorded', 'A payment of ' . rc_money($amountCents) . ' was recorded. Remaining balance: ' . rc_money($balance) . '.');
            rc_audit((int) $user['user_id'], 'Payment Recorded', 'Reservation', (string) $id, ['amount_paid' => $order['amount_paid'], 'balance_due' => $order['balance_due']], ['amount_paid' => rc_money_db($newPaid), 'balance_due' => rc_money_db($balance)]);
            $db->commit();
            rc_flash('success', 'Payment recorded and balance updated.');
            rc_redirect($user['role'] === 'Admin' ? 'orders' : 'owner');
        case 'delivery-status':
            $user = rc_require_owner();
            $id = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
            $next = (string) ($_POST['delivery_status'] ?? '');
            if (!$id || !in_array($next, ['Preparing', 'On the Way', 'Delivered'], true)) {
                rc_flash('error', 'Choose a valid delivery progress update.');
                rc_redirect('orders');
            }
            $db->beginTransaction();
            $stmt = $db->prepare('SELECT * FROM orders WHERE id=? FOR UPDATE');
            $stmt->execute([$id]);
            $order = $stmt->fetch();
            if (!$order || !in_array($order['status'], ['Confirmed', 'Processing'], true)) {
                $db->rollBack();
                rc_flash('error', 'Delivery progress can only be updated for confirmed or preparing reservations.');
                rc_redirect('orders');
            }
            $db->prepare('UPDATE orders SET delivery_status=? WHERE id=?')->execute([$next, $id]);
            rc_notify(null, $order['customer_id'] ? (int) $order['customer_id'] : null, (int) $id, 'Food Delivery Update', 'Your reservation #' . $id . ' is now: ' . $next . '.');
            rc_audit((int) $user['user_id'], 'Delivery Progress Updated', 'Reservation', (string) $id, ['delivery_status' => $order['delivery_status'] ?? 'Not Started'], ['delivery_status' => $next]);
            $db->commit();
            rc_flash('success', 'Customer delivery progress updated and notification sent.');
            rc_redirect('orders');
        case 'feedback':
            $user = rc_require_login();
            if ($user['role'] !== 'Customer') {
                rc_flash('error', 'Only customers can submit service feedback.');
                rc_redirect('feedback');
            }
            $customer = rc_customer_for($user);
            $orderId = filter_var($_POST['order_id'] ?? null, FILTER_VALIDATE_INT);
            $rating = filter_var($_POST['rating'] ?? null, FILTER_VALIDATE_INT);
            $comments = trim((string) ($_POST['comments'] ?? ''));
            $stmt = $db->prepare("SELECT id FROM orders WHERE id=? AND customer_id=? AND status='Completed' AND NOT EXISTS (SELECT 1 FROM feedback WHERE feedback.order_id=orders.id AND feedback.customer_id=orders.customer_id)");
            $stmt->execute([$orderId, $customer['customer_id']]);
            if (!$stmt->fetch() || $rating === false || $rating < 1 || $rating > 5 || mb_strlen($comments) > 3000) {
                rc_flash('error', 'Feedback requires one of your completed reservations and a rating from 1 to 5.');
                rc_redirect('feedback');
            }
            $imagePath = null;
            $savedImage = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $upload = $_FILES['image'];
                if ($upload['error'] !== UPLOAD_ERR_OK || $upload['size'] < 1 || $upload['size'] > 5 * 1024 * 1024) {
                    rc_flash('error', 'The optional image must be no larger than 5 MB.');
                    rc_redirect('feedback');
                }
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
                $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                if (!isset($extensions[$mime])) {
                    rc_flash('error', 'Upload a JPG, PNG, or WebP image.');
                    rc_redirect('feedback');
                }
                $uploadDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'feedback';
                if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
                    throw new RuntimeException('The feedback image folder could not be created.');
                }
                $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
                $savedImage = $uploadDirectory . DIRECTORY_SEPARATOR . $filename;
                if (!move_uploaded_file($upload['tmp_name'], $savedImage)) {
                    rc_flash('error', 'The feedback image could not be saved. Please try again.');
                    rc_redirect('feedback');
                }
                $imagePath = 'assets/feedback/' . $filename;
            }
            try {
                $db->prepare('INSERT INTO feedback (order_id, customer_id, rating, comments, image_path) VALUES (?, ?, ?, ?, ?)')->execute([$orderId, $customer['customer_id'], $rating, $comments !== '' ? $comments : null, $imagePath]);
            } catch (PDOException $error) {
                if ($savedImage && is_file($savedImage)) unlink($savedImage);
                if ($error->getCode() === '23000') {
                    rc_flash('error', 'Feedback has already been submitted for that reservation.');
                    rc_redirect('feedback');
                }
                throw $error;
            }
            rc_notify(null, null, (int) $orderId, 'Feedback Received', 'Feedback was submitted for reservation #' . $orderId . '.');
            rc_notify_owner((int) $orderId, 'New Feedback', 'New customer feedback was submitted for reservation #' . $orderId . '.');
            rc_flash('success', 'Thank you. Your feedback has been recorded and will appear after owner review.');
            rc_redirect('feedback');
        case 'feedback-reviewed':
            $user = rc_require_owner();
            $id = filter_var($_POST['feedback_id'] ?? null, FILTER_VALIDATE_INT);
            $stmt = $db->prepare('SELECT status FROM feedback WHERE feedback_id=?');
            $stmt->execute([$id]);
            $old = $stmt->fetchColumn();
            if ($old === false) {
                rc_flash('error', 'Feedback record not found.');
                rc_redirect('feedback-admin');
            }
            $db->prepare("UPDATE feedback SET status='Reviewed' WHERE feedback_id=?")->execute([$id]);
            rc_audit((int) $user['user_id'], 'Feedback Reviewed', 'Feedback', (string) $id, ['status' => $old], ['status' => 'Reviewed']);
            rc_flash('success', 'Feedback marked as reviewed. The original response was not changed.');
            rc_redirect('feedback-admin');
        case 'owner-profile':
            $user = rc_require_owner();
            $name = trim((string) ($_POST['full_name'] ?? ''));
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            if ($name === '' || mb_strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 254) {
                rc_flash('error', 'Enter a valid owner name and email address.');
                rc_redirect('settings');
            }
            $stmt = $db->prepare('SELECT full_name, email FROM users WHERE user_id=?');
            $stmt->execute([$user['user_id']]);
            $oldProfile = $stmt->fetch();
            try {
                $db->prepare('UPDATE users SET full_name=?, email=? WHERE user_id=?')->execute([$name, $email, $user['user_id']]);
            } catch (PDOException $error) {
                if ($error->getCode() === '23000') {
                    rc_flash('error', 'That email address is already used by another account.');
                    rc_redirect('settings');
                }
                throw $error;
            }
            $_SESSION['user']['full_name'] = $name;
            $_SESSION['user']['email'] = $email;
            rc_audit((int) $user['user_id'], 'Owner Profile Updated', 'User', (string) $user['user_id'], $oldProfile, ['full_name' => $name, 'email' => $email]);
            rc_flash('success', 'Owner profile updated.');
            rc_redirect('settings');
        case 'save-settings':
            $user = rc_require_owner();
            $allowedSettings = [
                'business_name' => ['Business name', 160, false],
                'business_email' => ['Business email', 254, true],
                'business_phone' => ['Business phone', 40, true],
                'business_address' => ['Business address', 500, true],
            ];
            $db->beginTransaction();
            foreach ($allowedSettings as $key => [$label, $limit, $optional]) {
                $value = trim((string) ($_POST[$key] ?? ''));
                if ((!$optional && $value === '') || mb_strlen($value) > $limit || ($key === 'business_email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL))) {
                    $db->rollBack();
                    rc_flash('error', 'Check the ' . $label . ' setting.');
                    rc_redirect('settings');
                }
                $stmt = $db->prepare('SELECT setting_value FROM system_settings WHERE setting_key=?');
                $stmt->execute([$key]);
                $old = $stmt->fetchColumn();
                $save = $db->prepare('INSERT INTO system_settings (setting_key, setting_value, updated_by) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_by=VALUES(updated_by)');
                $save->execute([$key, $value, $user['user_id']]);
                if ($old !== $value) rc_audit((int) $user['user_id'], 'Business Setting Updated', 'System Setting', $key, $old === false ? null : ['value' => $old], ['value' => $value]);
            }
            $db->commit();
            rc_flash('success', 'Business settings saved.');
            rc_redirect('settings');
        case 'menu-item':
            $user = rc_require_owner();
            $name = trim((string) ($_POST['name'] ?? ''));
            $category = trim((string) ($_POST['category'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $price = trim((string) ($_POST['price'] ?? ''));
            $unitType = (string) ($_POST['unit_type'] ?? 'tray_25_35pax');
            $componentType = (string) ($_POST['component_type'] ?? 'alacarte');
            $imageUrl = trim((string) ($_POST['image_url'] ?? ''));
            if ($name === '' || mb_strlen($name) > 255 || $category === '' || mb_strlen($category) > 50 || mb_strlen($description) > 5000 || mb_strlen($imageUrl) > 1000 || ($imageUrl !== '' && !preg_match('~^(https?://|/)[^\s<>"\']+$~i', $imageUrl)) || !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $price)) {
                rc_flash('error', 'Enter a valid name, category, description, and PHP price.');
                rc_redirect('owner');
            }
            if (!in_array($unitType, ['tray_25_35pax','per_pc','per_box','per_tray','per_layer','per_slice','per_person','each'], true) || !in_array($componentType, ['main_dish','side_dish','dessert','salad','rice','drink','alacarte'], true)) { rc_flash('error', 'Choose a valid serving unit and dish role.'); rc_redirect('menu-admin'); }
            $imageUrl = rc_uploaded_menu_image() ?? ($imageUrl ?: null);
            $db->prepare('INSERT INTO menu_items (name, description, image_url, price, unit_type, component_type, category, is_vegan, is_gluten_free, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)')->execute([$name, $description, $imageUrl, $price, $unitType, $componentType, $category, isset($_POST['is_vegan']) ? 1 : 0, isset($_POST['is_gluten_free']) ? 1 : 0]);
            $itemId = (int) $db->lastInsertId();
            rc_audit((int) $user['user_id'], 'Menu Item Created', 'Menu Item', (string) $itemId, null, ['name' => $name, 'price' => $price]);
            rc_flash('success', 'Menu item added.');
            rc_redirect('menu-admin');
        case 'package':
            $user = rc_require_owner();
            $name = trim((string) ($_POST['package_name'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            $price = trim((string) ($_POST['package_price'] ?? ''));
            $priceType = (string) ($_POST['price_type'] ?? 'Fixed');
            $packageType = (string) ($_POST['package_type'] ?? 'custom');
            $setName = trim((string) ($_POST['set_name'] ?? ''));
            $minimum = filter_var($_POST['minimum_guests'] ?? null, FILTER_VALIDATE_INT);
            $maximum = trim((string) ($_POST['maximum_guests'] ?? '')) === '' ? null : filter_var($_POST['maximum_guests'], FILTER_VALIDATE_INT);
            $imageUrl = trim((string) ($_POST['image_url'] ?? ''));
            $selected = $_POST['menu_items'] ?? [];
            $optionCharges = $_POST['option_charges'] ?? [];
            if ($name === '' || mb_strlen($name) > 160 || mb_strlen($description) > 5000 || mb_strlen($imageUrl) > 1000 || mb_strlen($setName)>80 || !in_array($packageType,['custom','packed_meal','buffet'],true) || ($imageUrl !== '' && !preg_match('~^(https?://|/)[^\s<>"\']+$~i', $imageUrl)) || !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $price) || !in_array($priceType, ['Fixed', 'Per Person'], true) || !$minimum || $minimum < 1 || (($packageType==='packed_meal'||$packageType==='buffet') && ($minimum<50 || $priceType!=='Per Person')) || ($maximum !== null && ($maximum === false || $maximum < $minimum)) || !is_array($selected) || !is_array($optionCharges)) {
                rc_flash('error', 'Complete all package fields using a valid price, minimum, and option list.');
                rc_redirect('packages-admin');
            }
            if (count($selected) < 1) {
                rc_flash('error', 'Each package needs at least one permitted food choice.');
                rc_redirect('packages-admin');
            }
            $imageUrl = rc_uploaded_menu_image() ?? ($imageUrl ?: null);
            $db->beginTransaction();
            $db->prepare("INSERT INTO catering_packages (package_name, package_type, set_name, description, image_url, price_type, package_price, minimum_guests, maximum_guests, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')")->execute([$name, $packageType, $setName ?: null, $description, $imageUrl, $priceType, $price, $minimum, $maximum]);
            $packageId = (int) $db->lastInsertId();
            $insert = $db->prepare("INSERT INTO package_items (package_id, menu_item_id, option_group, component_type, selection_rule, additional_charge, charge_type) SELECT ?, id, IF(?='packed_meal',IF(category='Add-ons','add_on',component_type),IF(?='buffet',CONCAT('Included: ',name),CONCAT(COALESCE(NULLIF(category, ''), 'Dish'), ' · ', name))), CASE WHEN category='Add-ons' THEN 'add_on' WHEN component_type IN ('main_dish','side_dish','dessert','salad','rice','drink') THEN component_type ELSE 'other' END, IF(?='packed_meal' AND (category='Add-ons' OR (?='Tier D' AND component_type='main_dish')),'Optional Many','Required One'), ?, IF(?='packed_meal' AND category='Add-ons','per_person','per_event') FROM menu_items WHERE id=? AND is_active=1");
            foreach (array_unique(array_map('intval', $selected)) as $itemId) {
                $charge = trim((string) ($optionCharges[$itemId] ?? '0'));
                if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $charge)) {
                    $db->rollBack();
                    rc_flash('error', 'Enter a valid non-negative surcharge for each selected menu choice.');
                    rc_redirect('packages-admin');
                }
                $insert->execute([$packageId, $packageType, $packageType, $packageType, $setName, $charge, $packageType, $itemId]);
                if ($insert->rowCount() !== 1) {
                    $db->rollBack();
                    rc_flash('error', 'A selected menu choice is missing or inactive. Refresh the package form and try again.');
                    rc_redirect('packages-admin');
                }
            }
            if ($packageType === 'buffet') {
                $fillBuffetOptions = $db->prepare("INSERT INTO package_items (package_id,menu_item_id,option_group,component_type,selection_rule,additional_charge,charge_type) SELECT DISTINCT base.package_id,alternative.id,base.option_group,base.component_type,'Required One',0,'per_person' FROM package_items base JOIN menu_items alternative ON alternative.component_type=base.component_type AND alternative.is_active=1 WHERE base.package_id=? AND base.option_group LIKE 'Included: %' AND NOT EXISTS (SELECT 1 FROM package_items existing WHERE existing.package_id=base.package_id AND existing.menu_item_id=alternative.id AND existing.option_group=base.option_group)");
                $fillBuffetOptions->execute([$packageId]);
            }
            if ($packageType === 'packed_meal') {
                $componentCounts = ['main_dish'=>0,'side_dish'=>0,'rice'=>0,'dessert'=>0,'salad'=>0];
                $selectedIds=array_values(array_unique(array_map('intval',$selected)));
                $componentQuery = $db->prepare("SELECT component_type,COUNT(*) AS item_count FROM menu_items WHERE is_active=1 AND id IN (" . implode(',', array_fill(0, count($selectedIds), '?')) . ") GROUP BY component_type");
                $componentQuery->execute($selectedIds);
                foreach ($componentQuery->fetchAll() as $component) $componentCounts[$component['component_type']] = (int)$component['item_count'];
                $required = ['main_dish'=>1,'side_dish'=>1,'rice'=>1,'dessert'=>0,'salad'=>0];
                if ($setName === 'Tier B') $required['dessert']=1;
                if ($setName === 'Tier C') { $required['dessert']=1; $required['salad']=1; }
                if ($setName === 'Tier D') { $required['main_dish']=2; $required['dessert']=1; $required['salad']=1; }
                foreach ($required as $component=>$minimumCount) {
                    if (($componentCounts[$component] ?? 0) < $minimumCount) { $db->rollBack(); rc_flash('error', 'This packed meal tier needs at least ' . $minimumCount . ' permitted ' . str_replace('_',' ',$component) . ' choice(s).'); rc_redirect('packages-admin'); }
                }
                $ruleInsert=$db->prepare('INSERT INTO package_component_rules (package_id,component_type,minimum_quantity,maximum_quantity) VALUES (?,?,?,?)');
                foreach ($required as $component=>$minimumCount) $ruleInsert->execute([$packageId,$component,$minimumCount,$minimumCount]);
                $ruleInsert->execute([$packageId,'add_on',0,20]);
            }
            rc_audit((int) $user['user_id'], 'Package Created', 'Package', (string) $packageId, null, ['name' => $name, 'price' => $price]);
            $db->commit();
            rc_flash('success', 'Catering package created.');
            rc_redirect('packages-admin');
        case 'toggle-active':
            $user = rc_require_owner();
            $kind = (string) ($_POST['kind'] ?? '');
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            if ($kind === 'item') {
                $itemState = $db->prepare('SELECT is_active FROM menu_items WHERE id=?');
                $itemState->execute([$id]);
                $wasActive = $itemState->fetchColumn();
                $activePackage = $db->prepare("SELECT COUNT(*) FROM package_items pi JOIN catering_packages p ON p.package_id=pi.package_id JOIN menu_items mi ON mi.id=pi.menu_item_id WHERE pi.menu_item_id=? AND p.status='Active' AND (p.package_type<>'buffet' OR mi.name=SUBSTRING(pi.option_group,11))");
                $activePackage->execute([$id]);
                if ((int) $wasActive === 1 && (int) $activePackage->fetchColumn() > 0) {
                    rc_flash('error', 'This dish is included in an active food package. Deactivate or update that package before archiving the dish.');
                    rc_redirect('menu-admin');
                }
                $db->prepare('UPDATE menu_items SET is_active = 1 - is_active WHERE id=?')->execute([$id]);
                $entity = 'Menu Item';
            } elseif ($kind === 'package') {
                $inactiveDish = $db->prepare("SELECT COUNT(*) FROM package_items pi JOIN menu_items mi ON mi.id=pi.menu_item_id JOIN catering_packages p ON p.package_id=pi.package_id WHERE pi.package_id=? AND mi.is_active=0 AND (p.package_type<>'buffet' OR mi.name=SUBSTRING(pi.option_group,11))");
                $inactiveDish->execute([$id]);
                $packageStatus = $db->prepare('SELECT status FROM catering_packages WHERE package_id=?');
                $packageStatus->execute([$id]);
                if ($packageStatus->fetchColumn() === 'Inactive' && (int) $inactiveDish->fetchColumn() > 0) {
                    rc_flash('error', 'Restore or replace the archived dishes before activating this food package.');
                    rc_redirect('packages-admin');
                }
                $db->prepare("UPDATE catering_packages SET status = IF(status='Active','Inactive','Active') WHERE package_id=?")->execute([$id]);
                $entity = 'Package';
            } else {
                rc_redirect('owner');
            }
            rc_audit((int) $user['user_id'], 'Offering Availability Changed', $entity, (string) $id);
            rc_flash('success', $entity . ' availability updated.');
            rc_redirect('owner');
        case 'toggle-item':
            $user = rc_require_owner();
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            $itemState = $db->prepare('SELECT is_active FROM menu_items WHERE id=?');
            $itemState->execute([$id]);
            $wasActive = $itemState->fetchColumn();
            $activePackage = $db->prepare("SELECT COUNT(*) FROM package_items pi JOIN catering_packages p ON p.package_id=pi.package_id JOIN menu_items mi ON mi.id=pi.menu_item_id WHERE pi.menu_item_id=? AND p.status='Active' AND (p.package_type<>'buffet' OR mi.name=SUBSTRING(pi.option_group,11))");
            $activePackage->execute([$id]);
            if ((int) $wasActive === 1 && (int) $activePackage->fetchColumn() > 0) {
                rc_flash('error', 'This dish is included in an active food package. Deactivate or update that package before archiving the dish.');
                rc_redirect('menu-admin');
            }
            $db->prepare('UPDATE menu_items SET is_active = 1 - is_active WHERE id=?')->execute([$id]);
            rc_audit((int) $user['user_id'], 'Menu Item Availability Changed', 'Menu Item', (string) $id);
            rc_flash('success', 'Menu item availability updated.');
            rc_redirect('menu-admin');
        case 'toggle-package':
            $user = rc_require_owner();
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            $inactiveDish = $db->prepare("SELECT COUNT(*) FROM package_items pi JOIN menu_items mi ON mi.id=pi.menu_item_id JOIN catering_packages p ON p.package_id=pi.package_id WHERE pi.package_id=? AND mi.is_active=0 AND (p.package_type<>'buffet' OR mi.name=SUBSTRING(pi.option_group,11))");
            $inactiveDish->execute([$id]);
            $packageStatus = $db->prepare('SELECT status FROM catering_packages WHERE package_id=?');
            $packageStatus->execute([$id]);
            if ($packageStatus->fetchColumn() === 'Inactive' && (int) $inactiveDish->fetchColumn() > 0) {
                rc_flash('error', 'Restore or replace the archived dishes before activating this food package.');
                rc_redirect('packages-admin');
            }
            $db->prepare("UPDATE catering_packages SET status = IF(status='Active','Inactive','Active') WHERE package_id=?")->execute([$id]);
            rc_audit((int) $user['user_id'], 'Package Availability Changed', 'Package', (string) $id);
            rc_flash('success', 'Package availability updated.');
            rc_redirect('packages-admin');
        case 'reset-user-password':
            $user = rc_require_owner();
            $id = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
            $password = (string) ($_POST['new_password'] ?? '');
            $stmt = $db->prepare("SELECT u.role, u.email FROM users u JOIN customers c ON c.user_id=u.user_id WHERE u.user_id=? AND u.role='Customer'");
            $stmt->execute([$id]);
            $target = $stmt->fetch();
            if (!$target || (int) $id === (int) $user['user_id'] || strlen($password) < 14) {
                rc_flash('error', 'Choose a customer account and enter a temporary password of at least 14 characters.');
                rc_redirect('users');
            }
            $db->prepare('UPDATE users SET password_hash=? WHERE user_id=?')->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
            rc_audit((int) $user['user_id'], 'Customer Password Reset', 'User', (string) $id, ['email' => $target['email']], ['password_changed' => true]);
            rc_flash('success', 'Customer password reset. Share the new password securely.');
            rc_redirect('users');
        case 'customer-status':
            $user = rc_require_owner();
            $id = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
            $status = (string) ($_POST['status'] ?? '');
            if (!$id || $id === (int) $user['user_id'] || !in_array($status, ['Active', 'Disabled'], true)) {
                rc_flash('error', 'Invalid user update.');
                rc_redirect('users');
            }
            $stmt = $db->prepare("SELECT u.role, u.status FROM users u JOIN customers c ON c.user_id=u.user_id WHERE u.user_id=? AND u.role='Customer'");
            $stmt->execute([$id]);
            $old = $stmt->fetch();
            if (!$old) {
                rc_flash('error', 'Customer account not found.');
                rc_redirect('users');
            }
            $db->prepare('UPDATE users SET status=? WHERE user_id=?')->execute([$status, $id]);
            rc_audit((int) $user['user_id'], 'Customer Access Updated', 'User', (string) $id, $old, ['role' => 'Customer', 'status' => $status]);
            rc_flash('success', 'Customer access updated.');
            rc_redirect('users');
        default:
            http_response_code(404);
            rc_render('error', ['title' => 'Action not found', 'message' => 'That action is not available.']);
    }
}

function rc_valid_date(string $date): bool
{
    $value = DateTime::createFromFormat('!Y-m-d', $date);
    return $value !== false && $value->format('Y-m-d') === $date;
}

function rc_valid_time(string $time): bool
{
    return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time) === 1;
}

function rc_render(string $view, array $data = []): void
{
    $flash = rc_take_flash();
    $user = $data['user'] ?? rc_user();
    $currentPage = $view === 'owner_login' ? 'owner-login' : $view;
    extract($data, EXTR_SKIP);
    ob_start();
    $file = __DIR__ . '/NativeViews/' . $view . '.php';
    if (!is_file($file)) $file = __DIR__ . '/NativeViews/error.php';
    require $file;
    $content = ob_get_clean();
    require __DIR__ . '/NativeViews/layout.php';
}
