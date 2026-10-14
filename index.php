<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/install.php';
require __DIR__ . '/includes/pages.php';
require __DIR__ . '/includes/certificate_pdf.php';

start_session();

$path = request_path();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($path === 'instalar' && $method === 'GET') {
    $key = (string) ($_GET['key'] ?? '');
    if (!hash_equals((string) config()['seed_key'], $key)) {
        http_response_code(404);
        exit('No encontrado');
    }
    try {
        header('Content-Type: text/plain; charset=utf-8');
        echo install_run();
    } catch (Throwable $e) {
        http_response_code(500);
        echo $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine();
    }
    exit;
}

$ready = false;
try {
    db()->query('SELECT 1 FROM levels LIMIT 1');
    $ready = true;
} catch (Throwable) {
    $ready = false;
}

if (!$ready) {
    render('preparing', ['title' => 'Preparando el curso'], 503);
}

if ($path === '' && $method === 'GET') {
    page_home();
}
if ($path === 'diccionario' && $method === 'GET') {
    page_dictionary();
}
if ($path === 'libros' && $method === 'GET') {
    page_books();
}
if (preg_match('#^libros/(\d+)/portada$#', $path, $m) && $method === 'GET') {
    book_cover((int) $m[1]);
}
if (preg_match('#^libros/(\d+)/descargar$#', $path, $m) && $method === 'GET') {
    download_book((int) $m[1]);
}
if ($path === 'cuenta' && $method === 'GET') {
    page_account();
}
if ($path === 'certificados' && $method === 'GET') {
    page_certificates();
}
if (preg_match('#^certificados/nivel/([1-9])$#', $path, $m) && $method === 'GET') {
    page_certificate_level((int) $m[1]);
}
if ($path === 'certificados/final' && $method === 'GET') {
    page_certificate_final();
}
if ($path === 'cuenta' && $method === 'POST') {
    post_account();
}
if ($path === 'auth/google' && $method === 'GET') {
    google_start();
}
if ($path === 'auth/google/callback' && $method === 'GET') {
    google_callback();
}
if (preg_match('#^media/avatar/(\d+)$#', $path, $m) && $method === 'GET') {
    serve_avatar((int) $m[1]);
}
if (preg_match('#^hsk-([1-9])$#', $path, $m) && $method === 'GET') {
    page_level((int) $m[1]);
}
if (preg_match('#^hsk-([1-9])/clase/([a-z0-9-]+)$#', $path, $m)) {
    if ($method === 'POST') {
        post_class((int) $m[1], $m[2]);
    }
    page_class((int) $m[1], $m[2]);
}

render('not-found', ['title' => 'No encontrado'], 404);
