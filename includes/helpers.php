<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    $base = rtrim((string) config()['base_path'], '/');
    $path = ltrim($path, '/');
    if ($path === '') {
        return $base . '/';
    }
    if (str_starts_with($path, 'assets/')) {
        return $base . '/' . $path;
    }
    return $base . '/index.php/' . $path;
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $sent = (string) ($_POST['csrf'] ?? '');
    if ($sent === '' || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('La sesión expiró. Volvé a intentar.');
    }
}

function flash(?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash_message'] = $message;
        return null;
    }
    $value = $_SESSION['flash_message'] ?? null;
    unset($_SESSION['flash_message']);
    return $value;
}

function current_user(): ?array
{
    static $loaded = false;
    static $user;
    if ($loaded) {
        return $user;
    }
    $loaded = true;
    $id = $_SESSION['user_id'] ?? null;
    if (!$id) {
        return $user = null;
    }
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([(int) $id]);
    $user = $stmt->fetch() ?: null;
    if (!$user) {
        unset($_SESSION['user_id']);
    }
    return $user;
}

function plain_pinyin(string $pinyin): string
{
    $map = [
        'ā' => 'a', 'á' => 'a', 'ǎ' => 'a', 'à' => 'a',
        'ē' => 'e', 'é' => 'e', 'ě' => 'e', 'è' => 'e',
        'ī' => 'i', 'í' => 'i', 'ǐ' => 'i', 'ì' => 'i',
        'ō' => 'o', 'ó' => 'o', 'ǒ' => 'o', 'ò' => 'o',
        'ū' => 'u', 'ú' => 'u', 'ǔ' => 'u', 'ù' => 'u',
        'ǖ' => 'v', 'ǘ' => 'v', 'ǚ' => 'v', 'ǜ' => 'v', 'ü' => 'v',
        'ń' => 'n', 'ň' => 'n', 'ǹ' => 'n',
    ];
    $lower = mb_strtolower($pinyin);
    $stripped = strtr($lower, $map);
    return preg_replace('/[^a-z0-9]+/', '', $stripped) ?? '';
}

function plain_es(string $text): string
{
    $lower = mb_strtolower($text);
    return strtr($lower, [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
    ]);
}

function short_gloss(string $text): string
{
    $part = preg_split('/[;；]/u', $text)[0] ?? $text;
    $part = trim($part);
    if (mb_strlen($part) > 90) {
        return mb_substr($part, 0, 87) . '…';
    }
    return $part;
}

function like_escape(string $value): string
{
    return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
}

function audio_src(string $text, string $pinyin = ''): string
{
    $key = trim($text);
    $pinyin = trim($pinyin);
    if ($pinyin !== '') {
        $key .= "\n" . $pinyin;
    }
    $id = substr(hash('sha1', $key), 0, 16);
    return url('assets/audio/' . $id . '.mp3');
}

function audio_button(string $text, string $pinyin = '', string $label = 'Escuchar'): string
{
    return '<button type="button" class="play" data-src="' . e(audio_src($text, $pinyin)) . '" aria-label="' . e($label) . '">'
        . '<span class="audio-dot" aria-hidden="true"></span><span>Escuchar</span></button>';
}

function format_bytes(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1048576) {
        return number_format($bytes / 1024, 0, ',', '.') . ' KB';
    }
    return number_format($bytes / 1048576, 1, ',', '.') . ' MB';
}

function http_request(string $method, string $url, ?array $form = null, ?string $bearer = null): array
{
    $ch = curl_init($url);
    $headers = [];
    if ($bearer) {
        $headers[] = 'Authorization: Bearer ' . $bearer;
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($form ?? []));
    }
    $body = curl_exec($ch);
    if ($body === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException($error);
    }
    curl_close($ch);
    $json = json_decode($body, true);
    return is_array($json) ? $json : [];
}

function fecha_es(string $datetime): string
{
    $months = [1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'];
    $ts = strtotime($datetime);
    if (!$ts) {
        return '';
    }
    return (int) date('j', $ts) . ' de ' . $months[(int) date('n', $ts)] . ' de ' . date('Y', $ts);
}

function render(string $template, array $data = [], int $status = 200): void
{
    http_response_code($status);
    $data['user'] = null;
    $data['levels'] = [];
    try {
        $data['user'] = current_user();
        $data['levels'] = db()->query('SELECT * FROM levels ORDER BY sort_order, id')->fetchAll();
    } catch (Throwable) {
        $data['user'] = null;
        $data['levels'] = [];
    }
    $data['title'] = $data['title'] ?? 'Aprendiendo mandarín';
    $data['flash'] = flash();
    extract($data, EXTR_SKIP);
    ob_start();
    include app_root() . '/templates/' . $template . '.php';
    $content = ob_get_clean();
    include app_root() . '/templates/layout.php';
    exit;
}

function request_path(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = rtrim((string) config()['base_path'], '/');
    if ($base !== '' && str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base));
    }
    $uri = trim($uri, '/');
    if ($uri === 'index.php') {
        return '';
    }
    if (str_starts_with($uri, 'index.php/')) {
        $uri = trim(substr($uri, strlen('index.php/')), '/');
    }
    return $uri;
}
