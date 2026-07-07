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
        redirect(url_for('login.php'));
    }
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
        error_log('[MasteRent] User groups query failed: ' . $exception->getMessage());
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
        error_log('[MasteRent] User services query failed: ' . $exception->getMessage());
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

    $content = render_template('dashboard.html', [
        'user_full_name' => e($_SESSION['user_full_name'] ?? ''),
        'user_email' => e($_SESSION['user_email'] ?? ''),
        'admin_stats' => '',
        'management_cards' => '',
        'group_rows' => '<p class="muted">Accesso negato.</p>',
        'service_rows' => '<p class="muted">Non hai il permesso richiesto: <code>' . e($serviceCode) . '</code>.</p>',
        'profile_url' => e(url_for('account/profile.php')),
        'logout_url' => e(url_for('logout.php')),
        'logout_form' => '<form method="POST" action="' . e(url_for('logout.php')) . '" class="inline-form">' . csrf_field('logout') . '<button type="submit" class="button-secondary">Logout</button></form>',
    ]);

    render_page('Accesso negato', $content, ['body_class' => 'page-dashboard']);
    exit;
}

function require_any_service(array $serviceCodes): void
{
    require_login();

    foreach ($serviceCodes as $serviceCode) {
        if (has_service((string) $serviceCode)) {
            return;
        }
    }

    require_service((string) ($serviceCodes[0] ?? ''));
}
