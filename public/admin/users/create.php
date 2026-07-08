<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/bootstrap.php';

require_login();
require_service('admin.users.manage');

$userRepo  = new UserRepository();
$groupRepo = new GroupRepository();

$id       = (int) query_str('id');
$editing  = $id > 0;
$user     = null;
$userGroupIds = [];

if ($editing) {
    $user = $userRepo->find($id);
    if ($user === null) {
        set_flash('danger', 'Utente non trovato.');
        redirect(url_for('admin/users/index.php'));
    }
    $userGroupIds = $userRepo->groupIdsForUser($id);
}

$allGroups = $groupRepo->all();
$errors = [];

/* ── POST: salvataggio ──────────────────────────────────────────── */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token(post_str('csrf_token'), 'user_form')) {
        $errors[] = 'Token CSRF non valido.';
    }

    $email     = strtolower(post_str('email'));
    $firstName = post_str('first_name');
    $lastName  = post_str('last_name');
    $phone     = post_str('phone');
    $status    = post_str('status');
    $password  = post_str('password');
    $groupIds  = array_map('intval', $_POST['groups'] ?? []);

    if ($email === '')     $errors[] = 'L\'email è obbligatoria.';
    if ($firstName === '') $errors[] = 'Il nome è obbligatorio.';
    if ($lastName === '')  $errors[] = 'Il cognome è obbligatorio.';
    if (!in_array($status, ['active', 'disabled'], true)) {
        $errors[] = 'Stato non valido.';
    }

    // Unicità email
    if ($email !== '') {
        $emailTaken = $editing
            ? $userRepo->emailExists($email) && strtolower((string) $user['email']) !== $email
            : $userRepo->emailExists($email);
        if ($emailTaken) {
            $errors[] = 'Questa email è già registrata.';
        }
    }

    // Password
    if (!$editing && $password === '') {
        $errors[] = 'La password è obbligatoria per i nuovi utenti.';
    }

    if ($errors === []) {
        $data = [
            'email'      => $email,
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'phone'      => $phone,
            'status'     => $status,
        ];

        if ($editing) {
            $userRepo->update($id, $data);
            if ($password !== '') {
                $userRepo->updatePassword($id, password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]));
            }
            $userRepo->setGroups($id, $groupIds);
            set_flash('success', 'Utente aggiornato.');
        } else {
            $data['password_hash'] = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $newId = $userRepo->create($data);
            $userRepo->setGroups($newId, $groupIds);
            set_flash('success', 'Utente creato.');
        }

        redirect(url_for('admin/users/index.php'));
    }

    // Ripopola i campi dopo errore
    $user = $user ?? [];
    $user['email']      = $email;
    $user['first_name'] = $firstName;
    $user['last_name']  = $lastName;
    $user['phone']      = $phone;
    $user['status']     = $status;
    $userGroupIds       = $groupIds;
}

/* ── Valori per il form ─────────────────────────────────────────── */

$v = [
    'email'      => $user['email'] ?? '',
    'first_name' => $user['first_name'] ?? '',
    'last_name'  => $user['last_name'] ?? '',
    'phone'      => $user['phone'] ?? '',
    'status'     => $user['status'] ?? 'active',
];

/* ── HTML ────────────────────────────────────────────────────────── */

$title = $editing ? 'Modifica utente' : 'Nuovo utente';

$html = '';
if ($errors !== []) {
    $html .= '<div class="alert alert-danger"><ul>';
    foreach ($errors as $err) {
        $html .= '<li>' . e($err) . '</li>';
    }
    $html .= '</ul></div>';
}

$html .= '<form class="admin-form" method="post">';
$html .= csrf_field('user_form');

$html .= '<div class="form-group">';
$html .= '<label for="email">Email</label>';
$html .= '<input type="email" id="email" name="email" value="' . e($v['email']) . '" required>';
$html .= '</div>';

$html .= '<div class="form-group">';
$html .= '<label for="first_name">Nome</label>';
$html .= '<input type="text" id="first_name" name="first_name" value="' . e($v['first_name']) . '" required>';
$html .= '</div>';

$html .= '<div class="form-group">';
$html .= '<label for="last_name">Cognome</label>';
$html .= '<input type="text" id="last_name" name="last_name" value="' . e($v['last_name']) . '" required>';
$html .= '</div>';

$html .= '<div class="form-group">';
$html .= '<label for="phone">Telefono</label>';
$html .= '<input type="text" id="phone" name="phone" value="' . e($v['phone']) . '">';
$html .= '</div>';

$html .= '<div class="form-group">';
$html .= '<label for="status">Stato</label>';
$html .= '<select id="status" name="status">';
$html .= select_options([
    ['id' => 'active',    'name' => 'Attivo'],
    ['id' => 'disabled', 'name' => 'Sospeso'],
], $v['status']);
$html .= '</select>';
$html .= '</div>';

$html .= '<div class="form-group">';
$html .= '<label for="password">Password' . ($editing ? ' (lascia vuoto per non modificare)' : '') . '</label>';
$html .= '<input type="password" id="password" name="password"' . ($editing ? '' : ' required') . '>';
$html .= '</div>';

$html .= '<fieldset class="form-group"><legend>Gruppi</legend>';
foreach ($allGroups as $g) {
    $checked = in_array((int) $g['id'], $userGroupIds, true) ? ' checked' : '';
    $html .= '<label class="checkbox-label">'
        . '<input type="checkbox" name="groups[]" value="' . e((string) $g['id']) . '"' . $checked . '> '
        . e($g['name'])
        . '</label> ';
}
$html .= '</fieldset>';

$html .= '<div class="form-actions">';
$html .= '<button class="button" type="submit">Salva</button> ';
$html .= '<a href="' . e(url_for('admin/users/index.php')) . '">Annulla</a>';
$html .= '</div>';

$html .= '</form>';

render_page_backend($title, $html, [], 'admin.users.index');
