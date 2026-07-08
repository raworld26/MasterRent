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
        error_log('[MasterRent] Login query failed: ' . $exception->getMessage());
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
        error_log('[MasterRent] Could not update last_login_at: ' . $exception->getMessage());
    }

    return true;
}

/* Esiste già un utente (non cancellato) con questa email? */
function email_exists(string $email): bool
{
    $stmt = db()->prepare('SELECT 1 FROM users WHERE email = :email AND deleted_at IS NULL LIMIT 1');
    $stmt->execute(['email' => strtolower(trim($email))]);

    return (bool) $stmt->fetchColumn();
}

/**
 * Registra un nuovo utente e gli assegna un gruppo (student/landlord).
 * L'account viene attivato automaticamente (decisione di progetto).
 * Ritorna l'id del nuovo utente.
 */
function register_user(array $data, string $groupCode): int
{
    $db = db();

    $stmt = $db->prepare(
        'INSERT INTO users (email, password_hash, first_name, last_name, phone, status, email_verified_at)
         VALUES (:email, :password_hash, :first_name, :last_name, :phone, :status, NOW())'
    );
    $stmt->execute([
        'email' => strtolower(trim((string) $data['email'])),
        'password_hash' => password_hash((string) $data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
        'first_name' => trim((string) $data['first_name']),
        'last_name' => trim((string) $data['last_name']),
        'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
        'status' => 'active',
    ]);

    $userId = (int) $db->lastInsertId();

    $stmt = $db->prepare(
        'INSERT IGNORE INTO users_has_groups (user_id, group_id)
         SELECT :user_id, g.id FROM user_groups AS g WHERE g.code = :code'
    );
    $stmt->execute(['user_id' => $userId, 'code' => $groupCode]);

    return $userId;
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
