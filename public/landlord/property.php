<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';

require_login();
require_service('landlord.home');

$user = current_user();
$uid = (int) $user['id'];
$propRepo = new PropertyRepository();
$roomRepo = new RoomRepository();
$bookingRepo = new BookingRepository();
$geo = new GeoRepository();

$id = (int) query_str('id');
$prop = $id > 0 ? $propRepo->find($id) : null;

if ($prop === null || (int) $prop['landlord_id'] !== $uid) {
    http_response_code(404);
    render_page_frontend('Annuncio non trovato',
        '<section class="panel empty-state"><h1>Annuncio non trovato</h1></section>',
        ['body_class' => 'page-dashboard']);
    exit;
}

$selfUrl = url_for('landlord/property.php?id=' . $id);

/* ---- POST actions ---- */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = post_str('action');

    if ($action === 'delete_property' && verify_csrf_token((string) ($_POST['csrf_token'] ?? ''), 'prop_delete')) {
        $propRepo->delete($id);
        set_flash('success', 'Annuncio eliminato.');
        redirect(url_for('landlord/index.php'));
    }

    if ($action === 'refresh_distances' && verify_csrf_token((string) ($_POST['csrf_token'] ?? ''), 'prop_pole')) {
        $distanceResult = (new MapDistanceService())->syncForProperty($prop, null, true);
        set_flash(($distanceResult['ok'] ?? false) ? 'success' : 'warning', (string) $distanceResult['message']);
        redirect($selfUrl);
    }

    if ($action === 'remove_pole' && verify_csrf_token((string) ($_POST['csrf_token'] ?? ''), 'prop_pole')) {
        set_flash('info', 'In fase 2 le distanze sono gestite automaticamente dalla mappa.');
        redirect($selfUrl);
    }

    if ($action === 'delete_image' && verify_csrf_token((string) ($_POST['csrf_token'] ?? ''), 'prop_img')) {
        $imgId = post_int('image_id');
        $img = $propRepo->findImage($imgId);
        if ($img !== null && (int) $img['property_id'] === $id) {
            $propRepo->deleteImage($imgId);
            // Rimuovi solo file caricati dal proprietario: non toccare demo condivise
            // o path manipolati salvati nel database.
            $filename = (string) $img['filename'];
            if (preg_match('/^prop_\d+_[a-f0-9]{16}\.(jpg|png|webp)$/', $filename)) {
                $uploadRoot = realpath(UPLOADS_DIR);
                $path = realpath(UPLOADS_DIR . '/' . $filename);
                if ($uploadRoot !== false && $path !== false
                    && str_starts_with($path, $uploadRoot . DIRECTORY_SEPARATOR)
                    && is_file($path)) {
                    @unlink($path);
                }
            }
            set_flash('info', 'Immagine eliminata.');
        }
        redirect($selfUrl);
    }


    if ($action === 'set_cover' && verify_csrf_token((string) ($_POST['csrf_token'] ?? ''), 'prop_img')) {
        $imgId = post_int('image_id');
        $propRepo->setCover($id, $imgId);
        set_flash('success', 'Copertina aggiornata.');
        redirect($selfUrl);
    }

    /*
     * Rimette in annuncio una stanza `reserved`/`unavailable` (§ HANDOFF 7.2).
     * Guardie: CSRF con scope dedicato, service dedicata, e la stanza deve
     * appartenere a QUESTO immobile (che sopra è già verificato del landlord).
     */
    if ($action === 'release_room' && verify_csrf_token((string) ($_POST['csrf_token'] ?? ''), 'room_release')) {
        require_service('landlord.room.release');

        $roomId = post_int('room_id');
        $room = $roomId > 0 ? $roomRepo->find($roomId) : null;

        if ($room === null || (int) $room['property_id'] !== $id) {
            set_flash('danger', 'Stanza non trovata in questo annuncio.');
        } elseif ((string) $room['status'] === 'available' && (int) $room['is_available'] === 1) {
            set_flash('info', 'La stanza è già disponibile.');
        } else {
            $bookingRepo->releaseRoom($roomId, $uid);
            set_flash('success', 'La stanza "' . $room['name'] . '" è di nuovo disponibile negli annunci.');
        }
        redirect($selfUrl);
    }

    redirect($selfUrl);
}

/* ---- Rooms HTML ---- */
$rooms = $roomRepo->byProperty($id);
$roomsHtml = '';
if ($rooms !== []) {
    $roomsHtml = '<table class="admin-table"><thead><tr><th>Nome</th><th>Tipo</th><th>Prezzo</th><th>Stato</th><th>Azioni</th></tr></thead><tbody>';
    foreach ($rooms as $r) {
        $status = (int) $r['is_available'] === 0 && (string) $r['status'] === 'available'
            ? 'unavailable'
            : (string) $r['status'];
            
        $tenantInfo = '';
        if ($status !== 'available') {
            $activeBooking = $bookingRepo->activeForRoom((int) $r['id']);
            if ($activeBooking) {
                $tenantInfo = '<div class="muted" style="margin-top: 4px; font-size: 0.85em;">'
                    . '<strong>Assegnata a:</strong> ' . e($activeBooking['first_name'] . ' ' . $activeBooking['last_name'])
                    . ' (' . e($activeBooking['email']) . ($activeBooking['phone'] ? ', ' . e($activeBooking['phone']) : '') . ')<br>'
                    . '<em>Stato prenotazione: ' . e($activeBooking['status']) . ' dal ' . date('d/m/Y', strtotime((string)$activeBooking['updated_at'])) . '</em></div>';
            }
        }
            
        $roomsHtml .= '<tr>';
        $roomsHtml .= '<td data-label="Nome">' . e($r['name']) . $tenantInfo . '</td>';
        $roomsHtml .= '<td data-label="Tipo">' . e(room_type_label((string) $r['type'])) . '</td>';
        $roomsHtml .= '<td data-label="Prezzo">' . e(format_price($r['price_monthly'])) . '</td>';
        $roomsHtml .= '<td data-label="Stato">' . render_badge_room_status($status) . '</td>';
        $roomsHtml .= '<td class="actions">';
        $roomsHtml .= '<a href="' . e(url_for('landlord/room_form.php?property_id=' . $id . '&id=' . $r['id'])) . '">Modifica</a> ';
        // "Rendi disponibile": solo per stanze non prenotabili
        if ($status !== 'available') {
            $roomsHtml .= '<form method="post" action="' . e($selfUrl) . '" class="inline-form" onsubmit="return confirm(\'Rimettere la stanza tra gli annunci disponibili?\')">';
            $roomsHtml .= csrf_field('room_release');
            $roomsHtml .= '<input type="hidden" name="action" value="release_room">';
            $roomsHtml .= '<input type="hidden" name="room_id" value="' . (int) $r['id'] . '">';
            $roomsHtml .= '<button type="submit" class="link-action">Rendi disponibile</button></form> ';
        }
        $roomsHtml .= '<form method="post" action="' . e(url_for('landlord/room_form.php?property_id=' . $id . '&id=' . $r['id'])) . '" class="inline-form" onsubmit="return confirm(\'Eliminare questa stanza?\')">';
        $roomsHtml .= csrf_field('room_delete');
        $roomsHtml .= '<input type="hidden" name="action" value="delete">';
        $roomsHtml .= '<button type="submit" class="link-danger">Elimina</button></form>';
        $roomsHtml .= '</td></tr>';
    }
    $roomsHtml .= '</tbody></table>';
}

/* ---- Images HTML ---- */
$images = $propRepo->imagesFor($id);
$imagesHtml = '';
foreach ($images as $img) {
    $imagesHtml .= '<div class="mng-image-card">';
    $imagesHtml .= '<img src="' . e(image_src($img['filename'])) . '" alt="' . e($img['caption'] ?? '') . '" loading="lazy">';
    $imagesHtml .= '<div class="mng-image-actions">';
    if ((int) $img['is_cover']) {
        $imagesHtml .= '<span class="badge badge-success">Copertina</span> ';
    } else {
        $imagesHtml .= '<form method="post" action="' . e($selfUrl) . '" class="inline-form">';
        $imagesHtml .= csrf_field('prop_img') . '<input type="hidden" name="action" value="set_cover"><input type="hidden" name="image_id" value="' . e($img['id']) . '">';
        $imagesHtml .= '<button type="submit" class="link-action">Imposta copertina</button></form> ';
    }
    $imagesHtml .= '<form method="post" action="' . e($selfUrl) . '" class="inline-form" onsubmit="return confirm(\'Eliminare immagine?\')">';
    $imagesHtml .= csrf_field('prop_img') . '<input type="hidden" name="action" value="delete_image"><input type="hidden" name="image_id" value="' . e($img['id']) . '">';
    $imagesHtml .= '<button type="submit" class="link-danger">Elimina</button></form>';
    $imagesHtml .= '</div></div>';
}

/* ---- Poles HTML ---- */
(new MapDistanceService())->ensureForProperty($prop);
$poles = $propRepo->polesFor($id);
$polesHtml = '';
if ($poles !== []) {
    $polesHtml = '<table class="admin-table"><thead><tr><th>Polo</th><th>Distanza</th><th>Fonte</th></tr></thead><tbody>';
    foreach ($poles as $p) {
        $polesHtml .= '<tr>';
        $polesHtml .= '<td>' . e($p['pole_name']) . '</td>';
        $polesHtml .= '<td>' . e($p['distance_minutes']) . ' min</td>';
        $polesHtml .= '<td>Mappa - ' . e(transit_label((string) $p['transit_type'])) . '</td>';
        $polesHtml .= '</tr>';
    }
    $polesHtml .= '</tbody></table>';
}

$content = render_template('frontend/landlord_property', [
    'back_url'       => e(url_for('landlord/index.php')),
    'prop_title'     => e($prop['title']),
    'prop_neighborhood' => e($prop['neighborhood_name']),
    'prop_address'   => e(trim($prop['address'] . ' ' . ($prop['house_number'] ?? ''))),
    'prop_desc'      => e($prop['description'] ?? ''),
    'edit_url'       => e(url_for('landlord/property_form.php?id=' . $id)),
    'delete_url'     => e($selfUrl),
    'csrf_delete'    => csrf_field('prop_delete'),
    'heating'        => e(heating_label((string) $prop['heating_type'])),
    'elevator'       => (int) $prop['has_elevator'] ? 'Sì' : 'No',
    'total_rooms'    => (string) (int) $prop['total_rooms'],
    'pub_rooms'      => (string) count($rooms),
    'add_room_url'   => e(url_for('landlord/room_form.php?property_id=' . $id)),
    'rooms_html'     => $roomsHtml,
    'upload_url'     => e(url_for('landlord/upload.php?property_id=' . $id)),
    'csrf_upload'    => csrf_field('prop_upload'),
    'images_html'    => $imagesHtml,
    'pole_action_url' => e($selfUrl),
    'csrf_pole'      => csrf_field('prop_pole'),
    'pole_options'   => select_options($geo->allPoles(), '', 'id', 'name', 'Seleziona polo'),
    'poles_html'     => $polesHtml,
]);

render_page_frontend('Gestione · ' . $prop['title'], $content, ['body_class' => 'page-dashboard']);
