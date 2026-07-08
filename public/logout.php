<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

/*
 * Logout con POST + token CSRF: evita che una pagina esterna possa
 * disconnettere l'utente con un semplice link/immagine (logout CSRF).
 * Una richiesta GET non disconnette: riporta alla home.
 */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirect(url_for('index.php'));
}

if (!verify_csrf_token((string) ($_POST['csrf_token'] ?? ''), 'logout')) {
    set_flash('danger', 'Sessione scaduta. Riprova.');
    redirect(url_for('index.php'));
}

logout_user();
redirect(url_for('index.php'));
