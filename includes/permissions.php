<?php

declare(strict_types=1);

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

/* L'utente corrente appartiene al gruppo indicato? */
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

/* Dopo il login, la pagina iniziale in base al ruolo principale. */
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

function user_groups(int $userId): array
{
    static $cache = [];

    if (array_key_exists($userId, $cache)) {
        return $cache[$userId];
    }

    try {
        $statement = db()->prepare(
            'SELECT g.id, g.code, g.name, g.description
             FROM users AS u
             JOIN users_has_groups AS uhg ON uhg.user_id = u.id
             JOIN user_groups AS g ON g.id = uhg.group_id
             WHERE u.id = :user_id
               AND u.status = :status
               AND u.deleted_at IS NULL
             ORDER BY g.name ASC'
        );
        $statement->execute([
            'user_id' => $userId,
            'status' => 'active',
        ]);

        $cache[$userId] = $statement->fetchAll();
    } catch (Throwable $exception) {
        error_log('[MasterRent] User groups query failed: ' . $exception->getMessage());
        $cache[$userId] = [];
    }

    return $cache[$userId];
}

function user_services(int $userId): array
{
    static $cache = [];

    if (array_key_exists($userId, $cache)) {
        return $cache[$userId];
    }

    try {
        $statement = db()->prepare(
            'SELECT DISTINCT
                s.id,
                s.code,
                s.name,
                s.description,
                s.area,
                s.path,
                s.http_method,
                s.is_menu_item,
                s.menu_order
             FROM users AS u
             JOIN users_has_groups AS uhg ON uhg.user_id = u.id
             JOIN user_groups AS g ON g.id = uhg.group_id
             JOIN services_has_groups AS shg ON shg.group_id = g.id
             JOIN services AS s ON s.id = shg.service_id
             WHERE u.id = :user_id
               AND u.status = :status
               AND u.deleted_at IS NULL
               AND s.is_active = 1
             ORDER BY s.area ASC, s.menu_order ASC, s.name ASC'
        );
        $statement->execute([
            'user_id' => $userId,
            'status' => 'active',
        ]);

        $cache[$userId] = $statement->fetchAll();
    } catch (Throwable $exception) {
        error_log('[MasterRent] User services query failed: ' . $exception->getMessage());
        $cache[$userId] = [];
    }

    return $cache[$userId];
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
        . '<p><a class="button-primary" href="' . e(role_home_url()) . '">Torna all\'area riservata</a></p>'
        . '</section>';

    render_page('Accesso negato', $content, ['body_class' => 'page-dashboard']);
    exit;
}

