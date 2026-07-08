<?php

declare(strict_types=1);

/*
 * Autenticazione e registrazione.
 */

function attempt_login(string $email, string $password): bool
{
    $email = strtolower(trim($email));

    if ($email === '' || $password === '') {
        return false;
    }

    try {
        $user = (new UserRepository())->findByEmail($email);
    } catch (Throwable $exception) {
        error_log('[MasterRent] Login query failed: ' . $exception->getMessage());
        return false;
    }

    if ($user === null) {
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

    (new UserRepository())->updateLastLogin((int) $user['id']);

    return true;
}

/**
 * Registra un nuovo utente e gli assegna un gruppo (student/landlord).
 * Ritorna l'id del nuovo utente.
 */
function register_user(array $data, string $groupCode): int
{
    $repository = new UserRepository();

    $userId = $repository->create([
        'email' => strtolower(trim($data['email'])),
        'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
        'first_name' => $data['first_name'],
        'last_name' => $data['last_name'],
        'phone' => $data['phone'] ?? '',
        'status' => 'active', // attivazione automatica (decisione di progetto)
    ]);

    $repository->attachGroupByCode($userId, $groupCode);

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
