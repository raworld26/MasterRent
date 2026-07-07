<?php

declare(strict_types=1);

function is_https_request(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? null) === '443');
}

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name('MASTERRENTSESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => is_https_request(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function url_for(string $path = ''): string
{
    $base = rtrim(BASE_URL, '/');
    $path = ltrim($path, '/');

    if ($path === '') {
        return $base === '' ? '/' : $base . '/';
    }

    return ($base === '' ? '' : $base) . '/' . $path;
}

function asset_url(string $path = ''): string
{
    $base = rtrim(ASSETS_URL, '/');
    $path = ltrim($path, '/');

    return $path === '' ? $base : $base . '/' . $path;
}

function csrf_token(string $scope = 'default'): string
{
    if (empty($_SESSION['_csrf'][$scope])) {
        $_SESSION['_csrf'][$scope] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf'][$scope];
}

function csrf_field(string $scope = 'default'): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token($scope)) . '">';
}

function verify_csrf_token(?string $token, string $scope = 'default'): bool
{
    if ($token === null || $token === '' || empty($_SESSION['_csrf'][$scope])) {
        return false;
    }

    return hash_equals($_SESSION['_csrf'][$scope], $token);
}

/* ---------------------------------------------------------------------------
 * Messaggi flash: sopravvivono a un solo redirect.
 * ------------------------------------------------------------------------- */
function set_flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $flashes = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);

    return $flashes;
}

/* Renderizza i flash come avvisi inline (nessun JavaScript). */
function render_flashes(): string
{
    $items = '';
    foreach (take_flashes() as $flash) {
        $type = in_array($flash['type'] ?? '', ['success', 'danger', 'info', 'warning'], true)
            ? $flash['type']
            : 'info';
        $items .= '<div class="alert alert-' . $type . '" role="alert">' . e($flash['message'] ?? '') . '</div>';
    }

    return $items === '' ? '' : '<div class="flash-area">' . $items . '</div>';
}

/* ---------------------------------------------------------------------------
 * Lettura input + piccole utilità.
 * ------------------------------------------------------------------------- */
function post_str(string $key, string $default = ''): string
{
    return trim((string) ($_POST[$key] ?? $default));
}

function post_int(string $key, int $default = 0): int
{
    return isset($_POST[$key]) && $_POST[$key] !== '' ? (int) $_POST[$key] : $default;
}

function query_str(string $key, string $default = ''): string
{
    return trim((string) ($_GET[$key] ?? $default));
}
