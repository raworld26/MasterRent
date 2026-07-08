<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';

require_login();
require_service('landlord.home');

$user = current_user();
$uid = (int) $user['id'];

$id = (int) query_str('id');
$prop = $id > 0 ? property_find($id) : null;

if ($prop === null || (int) $prop['landlord_id'] !== $uid) {
    http_response_code(404);
    render_page('Annuncio non trovato', '<section class="panel empty-state"><h1>Annuncio non trovato</h1></section>', ['body_class' => 'page-dashboard']);
    exit;
}

$selfUrl = url_for('landlord/property.php?id=' . $id);

/* ---- POST ---- */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = post_str('action');

    if ($action === 'delete_property' && verify_csrf_token((string) ($_POST['csrf_token'] ?? ''), 'prop_delete')) {
        property_delete($id);
        set_flash('success', 'Annuncio eliminato.');
        redirect(url_for('landlord/index.php'));
    }

    if ($action === 'set_pole' && verify_csrf_token((string) ($_POST['csrf_token'] ?? ''), 'prop_pole')) {
        $poleRaw = post_str('pole_id');
        $minutesRaw = post_str('distance_minutes');
        $transitRaw = post_str('transit_type', 'foot');

        if ($poleRaw === '' && $minutesRaw === '') {
            set_flash('info', 'Le distanze dai poli sono facoltative.');
        } elseif ($poleRaw === '' || $minutesRaw === '' || !is_numeric($minutesRaw) || (int) $minutesRaw < 1) {
            set_flash('warning', 'Per salvare una distanza seleziona un polo e inserisci minuti validi.');
        } else {
            $poleId = (int) $poleRaw;
            if ($poleId <= 0) {
                set_flash('warning', 'Seleziona un polo valido.');
            } else {
                $minutes = max(1, min(180, (int) $minutesRaw));
                $transit = in_array($transitRaw, ['foot', 'bus', 'car'], true) ? $transitRaw : 'foot';
                property_set_pole($id, $poleId, $minutes, $transit);
                set_flash('success', 'Distanza aggiornata.');
            }
        }
        redirect($selfUrl);
    }

    if ($action === 'remove_pole' && verify_csrf_token((string) ($_POST['csrf_token'] ?? ''), 'prop_pole')) {
        $poleId = post_int('pole_id');
        if ($poleId > 0) {
            property_remove_pole($id, $poleId);
            set_flash('info', 'Distanza rimossa.');
        }
        redirect($selfUrl);
    }

    if ($action === 'delete_image' && verify_csrf_token((string) ($_POST['csrf_token'] ?? ''), 'prop_img')) {
        $imgId = post_int('image_id');
        $img = property_find_image($imgId);
        if ($img !== null && (int) $img['property_id'] === $id) {
            property_delete_image($imgId);
            delete_uploaded_image_file((string) $img['filename']);
            set_flash('info', 'Immagine eliminata.');
        }
        redirect($selfUrl);
    }

    if ($action === 'set_cover' && verify_csrf_token((string) ($_POST['csrf_token'] ?? ''), 'prop_img')) {
        $imgId = post_int('image_id');
        $img = property_find_image($imgId);
        if ($img !== null && (int) $img['property_id'] === $id) {
            property_set_cover($id, $imgId);
            set_flash('success', 'Copertina aggiornata.');
        }
        redirect($selfUrl);
    }

    if ($action === 'release_room' && verify_csrf_token((string) ($_POST['csrf_token'] ?? ''), 'room_release')) {
        require_service('landlord.room.release');
        $roomId = post_int('room_id');
        $room = $roomId > 0 ? room_find($roomId) : null;
        if ($room === null || (int) $room['property_id'] !== $id) {
            set_flash('danger', 'Stanza non trovata in questo annuncio.');
        } elseif ((string) $room['status'] === 'available' && (int) $room['is_available'] === 1) {
            set_flash('info', 'La stanza è già disponibile.');
        } else {
            booking_release_room($roomId, $uid);
            set_flash('success', 'La stanza "' . $room['name'] . '" è di nuovo disponibile negli annunci.');
        }
        redirect($selfUrl);
    }

    redirect($selfUrl);
}

/* ---- Stanze ---- */
$rooms = rooms_by_property($id);
$roomsHtml = '';
if ($rooms === []) {
    $roomsHtml = '<p class="muted">Nessuna stanza. <a href="' . e(url_for('landlord/room_form.php?property_id=' . $id)) . '">Aggiungi una stanza</a>.</p>';
} else {
    $roomsHtml = '<table class="data-table"><thead><tr><th>Nome</th><th>Tipo</th><th>Prezzo</th><th>Stato</th><th>Azioni</th></tr></thead><tbody>';
    foreach ($rooms as $r) {
        $status = ((int) $r['is_available'] === 0 && (string) $r['status'] === 'available') ? 'unavailable' : (string) $r['status'];
        $tenantInfo = '';
        if ($status !== 'available') {
            $activeBooking = booking_active_for_room((int) $r['id']);
            if ($activeBooking !== null) {
                $tenantInfo = '<div class="muted" style="margin-top: 4px; font-size: 0.85em;">'
                    . '<strong>Assegnata a:</strong> ' . e($activeBooking['first_name'] . ' ' . $activeBooking['last_name'])
                    . ' (' . e($activeBooking['email']) . ($activeBooking['phone'] ? ', ' . e($activeBooking['phone']) : '') . ')<br>'
                    . '<em>Stato prenotazione: ' . e($activeBooking['status']) . ' dal ' . date('d/m/Y', strtotime((string)$activeBooking['updated_at'])) . '</em></div>';
            }
        }
        $actions = '<a class="button-small button-secondary" href="' . e(url_for('landlord/room_form.php?property_id=' . $id . '&id=' . $r['id'])) . '">Modifica</a> ';
        if ($status !== 'available' && has_service('landlord.room.release')) {
            $actions .= '<form method="post" action="' . e($selfUrl) . '" class="inline-form" onsubmit="return confirm(\'Rimettere la stanza tra gli annunci disponibili?\')">'
                . csrf_field('room_release') . '<input type="hidden" name="action" value="release_room"><input type="hidden" name="room_id" value="' . (int) $r['id'] . '">'
                . '<button type="submit" class="button-small">Rendi disponibile</button></form> ';
        }
        $actions .= '<form method="post" action="' . e(url_for('landlord/room_form.php?property_id=' . $id . '&id=' . $r['id'])) . '" class="inline-form" onsubmit="return confirm(\'Eliminare questa stanza?\')">'
            . csrf_field('room_delete') . '<input type="hidden" name="action" value="delete">'
            . '<button type="submit" class="button-small button-danger">Elimina</button></form>';
        $roomsHtml .= '<tr><td>' . e($r['name']) . $tenantInfo . '</td><td>' . e(room_type_label((string) $r['type'])) . '</td><td>' . e(format_price($r['price_monthly'])) . '</td>'
            . '<td>' . room_status_badge($status) . '</td><td>' . $actions . '</td></tr>';
    }
    $roomsHtml .= '</tbody></table>';
}

/* ---- Immagini ---- */
$images = property_images($id);
$imagesHtml = '';
foreach ($images as $img) {
    $imagesHtml .= '<figure class="thumb-media">'
        . property_image_markup($img['filename'], (string) ($img['caption'] ?? ''), 'admin-thumb')
        . '<figcaption>';
    if ((int) $img['is_cover']) {
        $imagesHtml .= '<span class="badge badge-success">Copertina</span> ';
    } else {
        $imagesHtml .= '<form method="post" action="' . e($selfUrl) . '" class="inline-form">'
            . csrf_field('prop_img') . '<input type="hidden" name="action" value="set_cover"><input type="hidden" name="image_id" value="' . (int) $img['id'] . '">'
            . '<button type="submit" class="button-small">Copertina</button></form> ';
    }
    $imagesHtml .= '<form method="post" action="' . e($selfUrl) . '" class="inline-form" onsubmit="return confirm(\'Eliminare immagine?\')">'
        . csrf_field('prop_img') . '<input type="hidden" name="action" value="delete_image"><input type="hidden" name="image_id" value="' . (int) $img['id'] . '">'
        . '<button type="submit" class="button-small button-danger">Elimina</button></form>'
        . '</figcaption></figure>';
}
if ($imagesHtml === '') {
    $imagesHtml = '<p class="muted">Nessuna immagine caricata.</p>';
}

/* ---- Distanze poli ---- */
$poles = property_poles($id);
$polesHtml = '';
if ($poles !== []) {
    $polesHtml = '<table class="data-table"><thead><tr><th>Polo</th><th>Distanza</th><th>Mezzo</th><th></th></tr></thead><tbody>';
    foreach ($poles as $p) {
        $polesHtml .= '<tr><td>' . e($p['pole_name']) . '</td><td>' . e((string) $p['distance_minutes']) . ' min</td><td>' . e(transit_label((string) $p['transit_type'])) . '</td>'
            . '<td><form method="post" action="' . e($selfUrl) . '" class="inline-form" onsubmit="return confirm(\'Rimuovere?\')">'
            . csrf_field('prop_pole') . '<input type="hidden" name="action" value="remove_pole"><input type="hidden" name="pole_id" value="' . (int) $p['pole_id'] . '">'
            . '<button type="submit" class="button-small button-danger">Rimuovi</button></form></td></tr>';
    }
    $polesHtml .= '</tbody></table>';
}

$address = trim($prop['address'] . ' ' . (string) ($prop['house_number'] ?? ''));

$content = '<section class="dashboard-shell">'
    . '<header class="dashboard-header"><div><p class="eyebrow">Gestione annuncio</p><h1>' . e($prop['title']) . '</h1>'
    . '<p class="muted">' . e($address . ' · ' . $prop['neighborhood_name']) . '</p></div>'
    . '<div class="actions-group">'
    . '<a class="button-secondary" href="' . e(url_for('landlord/index.php')) . '">I miei annunci</a> '
    . '<a class="button-secondary" href="' . e(url_for('landlord/property_form.php?id=' . $id)) . '">Modifica annuncio</a>'
    . '</div></header>'

    . '<section class="panel"><div class="panel-heading"><h2>Stanze (' . count($rooms) . ')</h2>'
    . '<a class="button-small" href="' . e(url_for('landlord/room_form.php?property_id=' . $id)) . '">+ Nuova stanza</a></div>'
    . $roomsHtml . '</section>'

    . '<section class="panel"><div class="panel-heading"><h2>Immagini (' . count($images) . ')</h2></div>'
    . '<form method="post" enctype="multipart/form-data" action="' . e(url_for('landlord/upload.php?property_id=' . $id)) . '" class="form-standard">'
    . csrf_field('prop_upload')
    . '<div class="form-grid">'
    . '<div class="form-group"><label>Nuova immagine</label><input type="file" name="image" accept="image/jpeg,image/png,image/webp" required></div>'
    . '<div class="form-group"><label>Didascalia</label><input type="text" name="caption" maxlength="150"></div>'
    . '</div>'
    . '<label class="check-row"><input type="checkbox" name="is_cover" value="1"> Imposta come copertina</label>'
    . '<div class="form-actions"><button type="submit" class="button-small">Carica immagine</button></div>'
    . '</form>'
    . '<div class="dashboard-properties-grid">' . $imagesHtml . '</div></section>'

    . '<section class="panel"><div class="panel-heading"><h2>Distanze dai poli</h2></div>'
    . '<p class="muted">Facoltativo: puoi aggiungere le distanze anche dopo la pubblicazione.</p>'
    . $polesHtml
    . '<form method="post" action="' . e($selfUrl) . '" class="form-standard">'
    . csrf_field('prop_pole') . '<input type="hidden" name="action" value="set_pole">'
    . '<div class="form-grid">'
    . '<div class="form-group"><label>Polo</label><select name="pole_id">' . select_options(poles_all(), '', 'id', 'name', 'Seleziona polo') . '</select></div>'
    . '<div class="form-group"><label>Minuti</label><input type="number" name="distance_minutes" min="1" max="180"></div>'
    . '<div class="form-group"><label>Mezzo</label><select name="transit_type">'
    . select_options([['id' => 'foot', 'name' => 'A piedi'], ['id' => 'bus', 'name' => 'Bus'], ['id' => 'car', 'name' => 'Auto']], 'foot', 'id', 'name')
    . '</select></div>'
    . '</div>'
    . '<div class="form-actions"><button type="submit" class="button-small">Aggiungi / aggiorna distanza</button></div></form></section>'

    . '<section class="panel"><div class="panel-heading"><h2>Elimina annuncio</h2></div>'
    . '<form method="post" action="' . e($selfUrl) . '" onsubmit="return confirm(\'Eliminare l\\\'annuncio e tutti i dati collegati?\')">'
    . csrf_field('prop_delete') . '<input type="hidden" name="action" value="delete_property">'
    . '<button type="submit" class="button-danger">Elimina annuncio</button></form></section>'
    . '</section>';

render_page('Gestione · ' . $prop['title'], $content, ['body_class' => 'page-dashboard']);
