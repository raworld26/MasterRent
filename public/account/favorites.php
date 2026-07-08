<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';

require_login();
require_service('account.favorites');

$user = current_user();
$rooms = (new FavoriteRepository())->roomsForUser((int) $user['id']);

$listHtml = $rooms === []
    ? render_empty_state(
        'Ancora nessun preferito',
        'Tocca il cuore su una stanza per salvarla qui e confrontarla con calma.',
        url_for('search.php'),
        'Esplora le stanze',
        'heart'
    )
    : render_room_grid($rooms);

$content = render_template('frontend/simple_page', [
    'page_title' => 'I miei preferiti',
    'page_intro' => 'Le stanze che hai salvato.',
    'page_action_url' => e(url_for('search.php')),
    'page_action_label' => 'Cerca stanze',
    'page_body' => $listHtml,
]);

render_page_frontend('I miei preferiti', $content, ['body_class' => 'page-dashboard']);
