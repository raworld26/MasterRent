<?php

declare(strict_types=1);

/*
 * Autorizzazione basata sul modello users-groups-services.
 * L'accesso a una pagina si verifica controllando che l'utente appartenga
 * a un gruppo che possiede il "servizio" (code) richiesto.
 */

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    return [
        'id' => (int) $_SESSION['user_id'],
        'email' => (string) ($_SESSION['user_email'] ?? ''),
        'full_name' => (string) ($_SESSION['user_full_name'] ?? ''),
    ];
}

function is_authenticated(): bool
{
    return current_user() !== null;
}

function require_login(): void
{
    if (!is_authenticated()) {
        set_flash('info', 'Accedi per continuare.');
        redirect(url_for('login.php'));
    }
}

function user_groups(int $userId): array
{
    static $cache = [];

    if (!array_key_exists($userId, $cache)) {
        try {
            $cache[$userId] = (new UserRepository())->groupsForUser($userId);
        } catch (Throwable $exception) {
            error_log('[MasterRent] User groups query failed: ' . $exception->getMessage());
            $cache[$userId] = [];
        }
    }

    return $cache[$userId];
}

function user_services(int $userId): array
{
    static $cache = [];

    if (!array_key_exists($userId, $cache)) {
        try {
            $cache[$userId] = (new UserRepository())->servicesForUser($userId);
        } catch (Throwable $exception) {
            error_log('[MasterRent] User services query failed: ' . $exception->getMessage());
            $cache[$userId] = [];
        }
    }

    return $cache[$userId];
}

function user_has_group(string $groupCode): bool
{
    $user = current_user();
    if ($user === null) {
        return false;
    }

    foreach (user_groups((int) $user['id']) as $group) {
        if (($group['code'] ?? '') === $groupCode) {
            return true;
        }
    }

    return false;
}

function has_service(string $serviceCode): bool
{
    $user = current_user();
    if ($user === null) {
        return false;
    }

    foreach (user_services((int) $user['id']) as $service) {
        if (($service['code'] ?? '') === $serviceCode) {
            return true;
        }
    }

    return false;
}

function require_service(string $serviceCode): void
{
    require_login();

    if (has_service($serviceCode)) {
        return;
    }

    http_response_code(403);
    $content = '<section class="panel">'
        . '<h1>Accesso negato</h1>'
        . '<p class="muted">Non disponi del permesso richiesto: <code>' . e($serviceCode) . '</code>.</p>'
        . '<p><a class="button" href="' . e(url_for('login.php')) . '">Torna all\'accesso</a></p>'
        . '</section>';
    render_page_backend('Accesso negato', $content);
    exit;
}

/**
 * Dopo il login, decide la pagina iniziale in base al ruolo principale.
 */
function role_home_url(): string
{
    if (user_has_group('admin')) {
        return url_for('admin/index.php');
    }
    if (user_has_group('landlord')) {
        return url_for('landlord/index.php');
    }
    if (user_has_group('student')) {
        return url_for('account/index.php');
    }

    return url_for('index.php');
}

/**
 * Voci di menu del backend, generate dai servizi accessibili all'utente
 * (area=backend e is_menu_item=1). Usato dalla sidebar admin.
 *
 * @return array<int,array{name:string,url:string,active:bool}>
 */
function backend_menu_items(string $activeCode = ''): array
{
    $user = current_user();
    if ($user === null) {
        return [];
    }

    $items = [];
    foreach (user_services((int) $user['id']) as $service) {
        if (($service['area'] ?? '') !== 'backend' || (int) ($service['is_menu_item'] ?? 0) !== 1) {
            continue;
        }

        $items[] = [
            'name' => (string) $service['name'],
            'url' => url_for(ltrim((string) $service['path'], '/')),
            'active' => ($service['code'] ?? '') === $activeCode,
        ];
    }

    return $items;
}
