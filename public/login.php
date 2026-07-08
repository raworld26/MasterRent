<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (is_authenticated()) {
    redirect(role_home_url());
}

$error = '';
$email = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $email = post_str('email');
    $password = (string) ($_POST['password'] ?? '');
    $csrfToken = (string) ($_POST['csrf_token'] ?? '');

    if (!verify_csrf_token($csrfToken, 'login')) {
        $error = 'Sessione scaduta. Riprova.';
    } elseif (attempt_login($email, $password)) {
        set_flash('success', 'Accesso effettuato. Bentornato!');
        redirect(role_home_url());
    } else {
        $error = 'Email o password non corretti.';
    }
}

$content = render_template('login.html', [
    'login_action' => e(url_for('login.php')),
    'register_url' => e(url_for('register.php')),
    'csrf_field' => csrf_field('login'),
    'email' => e($email),
    'error' => $error === '' ? '' : '<div class="alert alert-danger" role="alert">' . e($error) . '</div>',
]);

render_page('Accesso', $content, ['body_class' => 'page-login']);
