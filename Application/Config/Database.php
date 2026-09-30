<?php

declare(strict_types=1);

namespace Application\Config;

/**
 * Legacy database defaults retained for the older controller files.
 * The active native application reads RIZ_DB_* environment variables in
 * Application/NativeApp.php instead.
 */
final class Database
{
    public array $default = [
        'DSN'      => '',
        'hostname' => 'localhost',
        'username' => 'root',
        'password' => '',
        'database' => 'riz_catering',
        'DBDriver' => 'MySQLi',
        'DBPrefix' => '',
        'pConnect' => false,
        'DBDebug'  => true,
    ];
}
