<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (is_authenticated()) {
    redirect(role_home_url());
}

$errors = [];
$role = 'student';
$firstName = '';
$lastName = '';
$email = '';
$phone = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $role = post_str('role', 'student');
    $firstName = post_str('first_name');
    $lastName = post_str('last_name');
    $email = strtolower(post_str('email'));
    $phone = post_str('phone');
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
    $csrfToken = (string) ($_POST['csrf_token'] ?? '');

    if (!verify_csrf_token($csrfToken, 'register')) {
        $errors[] = 'Sessione scaduta o richiesta non valida. Riprova.';
    }
    if (!in_array($role, ['student', 'landlord'], true)) {
        $errors[] = 'Seleziona un tipo di account valido.';
    }
    if ($firstName === '' || $lastName === '') {
        $errors[] = 'Nome e cognome sono obbligatori.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Inserisci un indirizzo email valido.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'La password deve avere almeno 8 caratteri.';
    }
    if ($password !== $passwordConfirm) {
        $errors[] = 'Le due password non coincidono.';
    }
    if ($errors === [] && email_exists($email)) {
        $errors[] = 'Esiste già un account con questa email.';
    }

    if ($errors === []) {
        try {
            register_user([
                'email' => $email,
                'password' => $password,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
            ], $role);

            attempt_login($email, $password);
            set_flash('success', 'Registrazione completata. Benvenuto!');
            redirect(role_home_url());
        } catch (Throwable $exception) {
            error_log('[MasterRent] Registration failed: ' . $exception->getMessage());
            $errors[] = 'Si è verificato un errore. Riprova più tardi.';
        }
    }
}

$errorHtml = '';
if ($errors !== []) {
    $items = '';
    foreach ($errors as $message) {
        $items .= '<li>' . e($message) . '</li>';
    }
    $errorHtml = '<div class="alert alert-danger" role="alert"><ul>' . $items . '</ul></div>';
}

$content = render_template('register.html', [
    'register_action' => e(url_for('register.php')),
    'login_url' => e(url_for('login.php')),
    'csrf_field' => csrf_field('register'),
    'student_checked' => $role === 'student' ? 'checked' : '',
    'landlord_checked' => $role === 'landlord' ? 'checked' : '',
    'first_name' => e($firstName),
    'last_name' => e($lastName),
    'email' => e($email),
    'phone' => e($phone),
    'error' => $errorHtml,
]);

render_page('Registrati', $content, ['body_class' => 'page-login']);
