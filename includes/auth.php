<?php

declare(strict_types=1);

function attempt_login(string $email, string $password): bool
{
    $email = strtolower(trim($email));

    if ($email === '' || $password === '') {
        return false;
    }

    try {
        $statement = db()->prepare(
            'SELECT id, email, password_hash, first_name, last_name, status, deleted_at
             FROM users
             WHERE email = :email
             LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();
    } catch (Throwable $exception) {
        error_log('[MasteRent] Login query failed: ' . $exception->getMessage());
        return false;
    }

    if (!$user) {
        return false;
    }

    if (($user['status'] ?? '') !== 'active' || !empty($user['deleted_at'])) {
        return false;
    }

    if (!password_verify($password, (string) $user['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);

    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_email'] = (string) $user['email'];
    $_SESSION['user_full_name'] = trim($user['first_name'] . ' ' . $user['last_name']);

    try {
        $statement = db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $statement->execute(['id' => (int) $user['id']]);
    } catch (Throwable $exception) {
        error_log('[MasteRent] Could not update last_login_at: ' . $exception->getMessage());
    }

    return true;
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}
