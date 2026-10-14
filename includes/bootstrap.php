<?php

declare(strict_types=1);

function app_root(): string
{
    return dirname(__DIR__);
}

function config(): array
{
    static $config;
    if ($config === null) {
        $path = app_root() . '/config.php';
        if (!is_file($path)) {
            throw new RuntimeException('Falta config.php');
        }
        $config = require $path;
    }
    return $config;
}

function db_server(): PDO
{
    $c = config()['db'];
    return new PDO(
        sprintf('mysql:host=%s;port=%d;charset=%s', $c['host'], $c['port'], $c['charset']),
        $c['user'],
        $c['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
}

function db(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $c = config()['db'];
    $pdo = new PDO(
        sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $c['host'],
            $c['port'],
            $c['name'],
            $c['charset']
        ),
        $c['user'],
        $c['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    return $pdo;
}

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $path = config()['base_path'] ?: '/';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $path,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
