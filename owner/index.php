<?php
declare(strict_types=1);

define('RC_PORTAL', 'owner');
require_once dirname(__DIR__) . '/Application/NativeApp.php';
rc_boot();
$_GET['page'] = (string) ($_GET['page'] ?? 'owner');
rc_dispatch();
