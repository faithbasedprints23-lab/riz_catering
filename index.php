<?php
declare(strict_types=1);

// Compatibility entry point: send public pages to the customer portal and
// management pages to the owner portal. New links use the portal entrypoints.
$page = (string) ($_GET['page'] ?? 'home');
$ownerPages = [
    'owner'=>'dashboard','orders'=>'orders','operations'=>'operations','feedback-admin'=>'feedback',
    'menu-admin'=>'menu','packages-admin'=>'packages','sales'=>'sales','settings'=>'settings',
    'customers'=>'customers','users'=>'users','audit'=>'audit','calendar'=>'calendar','reports'=>'reports',
    'password'=>'password','notifications'=>'notifications','offerings'=>'offerings','database'=>'database',
    'owner-login'=>'login','install'=>'register',
];
$base = rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
$base = $base === '.' ? '' : $base;
if (isset($ownerPages[$page])) {
    $destination = $base . '/owner/' . $ownerPages[$page];
    $params = $_GET;
    unset($params['page']);
    if ($params) $destination .= '?' . http_build_query($params);
} else {
    $destination = $base . '/customer/';
    if ($page !== 'home' || count($_GET) > 0) {
        $destination = $base . '/customer/index.php?' . http_build_query(['page'=>$page] + array_diff_key($_GET, ['page'=>true]));
    }
}
header('Location: ' . $destination, true, 302);
exit;
