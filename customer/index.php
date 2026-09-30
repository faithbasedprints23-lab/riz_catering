<?php
declare(strict_types=1);

define('RC_PORTAL', 'customer');
require_once dirname(__DIR__) . '/Application/NativeApp.php';
rc_boot();
$_GET['page'] = (string) ($_GET['page'] ?? 'home');
rc_dispatch();
