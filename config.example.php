<?php

declare(strict_types=1);

return [
    'base_path' => '/aprendiendomandarin',
    'app_url' => 'http://192.168.100.50/aprendiendomandarin',
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'aprendiendomandarin',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'google' => [
        'client_id' => '',
        'client_secret' => '',
        'redirect' => 'http://192.168.100.50/aprendiendomandarin/index.php/auth/google/callback',
    ],
    'seed_key' => 'change-me',
];
