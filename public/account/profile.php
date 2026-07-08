<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';

require_login();

$repo = new UserRepository();
$user = current_user();
$record = $repo->find((int) $user['id']);

if ($record === null) {
    logout_user();
    redirect(url_for('login.php'));
}

$errorProfile = '';
$errorPassword = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = post_str('action');

    if ($action === 'profile') {
        $firstName = post_str('first_name');
        $lastName = post_str('last_name');
        $phone = post_str('phone');
        if (!verify_csrf_token((string) ($_POST['csrf_token'] ?? ''), 'profile')) {
            $errorProfile = 'Sessione scaduta. Riprova.';
        } elseif ($firstName === '' || $lastName === '') {
            $errorProfile = 'Nome e cognome sono obbligatori.';
        } else {
            $repo->updateProfile((int) $user['id'], $firstName, $lastName, $phone);
            $_SESSION['user_full_name'] = trim($firstName . ' ' . $lastName);
            set_flash('success', 'Profilo aggiornato.');
            redirect(url_for('account/profile.php'));
        }
    } elseif ($action === 'password') {
        $current = (string) ($_POST['current_password'] ?? '');
        $new = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');
        if (!verify_csrf_token((string) ($_POST['csrf_token'] ?? ''), 'password')) {
            $errorPassword = 'Sessione scaduta. Riprova.';
        } elseif (!password_verify($current, (string) $record['password_hash'])) {
            $errorPassword = 'La password attuale non è corretta.';
        } elseif (strlen($new) < 8) {
            $errorPassword = 'La nuova password deve avere almeno 8 caratteri.';
        } elseif ($new !== $confirm) {
            $errorPassword = 'Le due password non coincidono.';
        } else {
            $repo->updatePassword((int) $user['id'], password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]));
            set_flash('success', 'Password aggiornata.');
            redirect(url_for('account/profile.php'));
        }
    }

    // In caso di errore, ricarica i dati correnti aggiornando con l'input.
    $record = array_merge($record, [
        'first_name' => post_str('first_name', $record['first_name']),
        'last_name' => post_str('last_name', $record['last_name']),
        'phone' => post_str('phone', (string) ($record['phone'] ?? '')),
    ]);
}

$content = render_template('frontend/profile', [
    'action_url' => e(url_for('account/profile.php')),
    'csrf_profile' => csrf_field('profile'),
    'csrf_password' => csrf_field('password'),
    'first_name' => e($record['first_name']),
    'last_name' => e($record['last_name']),
    'email' => e($record['email']),
    'phone' => e((string) ($record['phone'] ?? '')),
    'error_profile' => $errorProfile === '' ? '' : '<div class="alert alert-danger">' . e($errorProfile) . '</div>',
    'error_password' => $errorPassword === '' ? '' : '<div class="alert alert-danger">' . e($errorPassword) . '</div>',
]);

render_page_frontend('Il mio profilo', $content, ['body_class' => 'page-dashboard']);
