<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/bootstrap.php';

require_login();
require_service('admin.properties.manage');

$propRepo = new PropertyRepository();
$roomRepo = new RoomRepository();
$geoRepo = new GeoRepository();

function admin_property_save_uploaded_image(PropertyRepository $repo, int $propertyId, array &$errors): int
{
    $file = $_FILES['image'] ?? null;
    if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Seleziona una immagine da caricare.';
        return 0;
    }

    if ((int) ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        $errors[] = 'Upload non riuscito.';
        return 0;
    }

    if ((int) ($file['size'] ?? 0) > UPLOAD_MAX_BYTES) {
        $errors[] = 'Immagine oltre il limite di 4 MB.';
        return 0;
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    $allowedMimes = explode(',', UPLOAD_ALLOWED_MIME);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $tmpName !== '' ? $finfo->file($tmpName) : false;

    if (!is_string($mime) || !in_array($mime, $allowedMimes, true)) {
        $errors[] = 'Formato immagine non supportato.';
        return 0;
    }

    if (!is_dir(UPLOADS_DIR)) {
        mkdir(UPLOADS_DIR, 0755, true);
    }

    $filename = 'prop_' . $propertyId . '_' . bin2hex(random_bytes(8)) . '.' . ($extensions[$mime] ?? 'jpg');
    $destination = UPLOADS_DIR . '/' . $filename;

    if (!move_uploaded_file($tmpName, $destination)) {
        $errors[] = 'Immagine non salvata.';
        return 0;
    }

    $caption = post_str('caption');
    $isCover = isset($_POST['is_cover']) || $repo->coverFor($propertyId) === null;
    $imageId = $repo->addImage($propertyId, $filename, false, $caption !== '' ? $caption : null);
    if ($isCover) {
        $repo->setCover($propertyId, $imageId);
    }

    return $imageId;
}

function admin_property_safe_unlink(?string $filename): void
{
    $filename = trim((string) $filename);
    if ($filename === '' || !preg_match('/^prop_[0-9]+_[a-f0-9]{16}\.(jpg|png|webp)$/', $filename)) {
        return;
    }

    $path = realpath(UPLOADS_DIR . '/' . $filename);
    $uploadsRoot = realpath(UPLOADS_DIR);
    if ($path !== false && $uploadsRoot !== false && str_starts_with($path, $uploadsRoot) && is_file($path)) {
        unlink($path);
    }
}

$id = (int) query_str('id');
$property = $id > 0 ? $propRepo->find($id) : null;

if ($property === null) {
    set_flash('danger', 'Annuncio non trovato.');
    redirect(url_for('admin/properties/index.php'));
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verify_csrf_token(post_str('csrf_token'))) {
        set_flash('danger', 'Sessione scaduta. Riprova.');
        redirect(url_for('admin/properties/view.php?id=' . $id));
    }

    $action = post_str('action');

    if ($action === 'delete_property') {
        $propRepo->delete($id);
        set_flash('success', 'Annuncio eliminato.');
        redirect(url_for('admin/properties/index.php'));
    }

    if ($action === 'delete_room') {
        $roomId = post_int('room_id');
        if ($roomId > 0) {
            $room = $roomRepo->find($roomId);
            if ($room !== null && (int) $room['property_id'] === $id) {
                $roomRepo->delete($roomId);
                set_flash('success', 'Stanza eliminata.');
            }
        }
        redirect(url_for('admin/properties/view.php?id=' . $id));
    }

    if ($action === 'upload_image') {
        $errors = [];
        $imageId = admin_property_save_uploaded_image($propRepo, $id, $errors);
        if ($imageId > 0) {
            set_flash('success', 'Immagine caricata.');
        } else {
            set_flash('danger', implode(' ', array_unique($errors)));
        }
        redirect(url_for('admin/properties/view.php?id=' . $id));
    }

    if ($action === 'set_cover') {
        $imageId = post_int('image_id');
        $image = $imageId > 0 ? $propRepo->findImage($imageId) : null;
        if ($image !== null && (int) $image['property_id'] === $id) {
            $propRepo->setCover($id, $imageId);
            set_flash('success', 'Copertina aggiornata.');
        }
        redirect(url_for('admin/properties/view.php?id=' . $id));
    }

    if ($action === 'delete_image') {
        $imageId = post_int('image_id');
        $image = $imageId > 0 ? $propRepo->findImage($imageId) : null;
        if ($image !== null && (int) $image['property_id'] === $id) {
            admin_property_safe_unlink((string) $image['filename']);
            $propRepo->deleteImage($imageId);
            set_flash('success', 'Immagine eliminata.');
        }
        redirect(url_for('admin/properties/view.php?id=' . $id));
    }

    if ($action === 'refresh_distances') {
        $distanceResult = (new MapDistanceService())->syncForProperty($property, null, true);
        set_flash(($distanceResult['ok'] ?? false) ? 'success' : 'warning', (string) $distanceResult['message']);
        redirect(url_for('admin/properties/view.php?id=' . $id));
    }

    if ($action === 'remove_pole') {
        $poleId = post_int('pole_id');
        if ($poleId > 0) {
            $propRepo->removePole($id, $poleId);
            set_flash('success', 'Distanza rimossa.');
        }
        redirect(url_for('admin/properties/view.php?id=' . $id));
    }

    redirect(url_for('admin/properties/view.php?id=' . $id));
}

(new MapDistanceService())->ensureForProperty($property);
$rooms = $roomRepo->byProperty($id);
$images = $propRepo->imagesFor($id);
$poles = $propRepo->polesFor($id);

$html = '<div class="admin-toolbar">';
$html .= '<h2>' . e($property['title']) . '</h2>';
$html .= '<a href="' . e(url_for('admin/properties/index.php')) . '" class="btn btn-sm btn-secondary">Elenco</a> ';
$html .= '<a href="' . e(url_for('admin/properties/form.php?id=' . $id)) . '" class="btn btn-sm btn-info">Modifica annuncio</a> ';
$html .= '<form method="post" style="display:inline" onsubmit="return confirm(\'Eliminare l annuncio e tutti i dati collegati?\')">';
$html .= csrf_field();
$html .= '<input type="hidden" name="action" value="delete_property">';
$html .= '<button type="submit" class="btn btn-sm btn-danger">Elimina annuncio</button>';
$html .= '</form>';
$html .= '</div>';

$html .= '<table class="admin-table">';
$html .= '<tbody>';
$html .= '<tr><th>ID</th><td>' . e($property['id']) . '</td></tr>';
$html .= '<tr><th>Titolo</th><td>' . e($property['title']) . '</td></tr>';
$html .= '<tr><th>Quartiere</th><td>' . e($property['neighborhood_name']) . '</td></tr>';
$html .= '<tr><th>Indirizzo</th><td>' . e($property['address']) . ' ' . e($property['house_number']) . ', ' . e($property['postal_code']) . '</td></tr>';
$html .= '<tr><th>Proprietario</th><td>' . e($property['landlord_first'] . ' ' . $property['landlord_last']) . ' (' . e($property['landlord_email']) . ')</td></tr>';
$html .= '<tr><th>Descrizione</th><td>' . nl2br(e($property['description'])) . '</td></tr>';
$html .= '<tr><th>Stanze totali</th><td>' . e($property['total_rooms']) . '</td></tr>';
$html .= '<tr><th>Ascensore</th><td>' . ($property['has_elevator'] ? 'Si' : 'No') . '</td></tr>';
$html .= '<tr><th>Riscaldamento</th><td>' . e(heating_label((string) $property['heating_type'])) . '</td></tr>';
$html .= '</tbody></table>';

$html .= '<div class="admin-toolbar" style="margin-top:1.5rem">';
$html .= '<h3>Stanze (' . count($rooms) . ')</h3>';
$html .= '<a class="button" href="' . e(url_for('admin/rooms/form.php?property_id=' . $id)) . '">+ Nuova stanza</a>';
$html .= '</div>';
$html .= '<table class="admin-table"><thead><tr>';
$html .= '<th>ID</th><th>Nome stanza</th><th>Tipo</th><th>Prezzo</th><th>Stato</th><th>Azioni</th>';
$html .= '</tr></thead><tbody>';

foreach ($rooms as $room) {
    $status = (string) ($room['status'] ?? ($room['is_available'] ? 'available' : 'unavailable'));
    $html .= '<tr>';
    $html .= '<td>' . e($room['id']) . '</td>';
    $html .= '<td>' . e($room['name']) . '</td>';
    $html .= '<td>' . e(room_type_label((string) $room['type'])) . '</td>';
    $html .= '<td>' . e(format_price($room['price_monthly'])) . '</td>';
    $html .= '<td>' . render_badge_room_status($status) . '</td>';
    $html .= '<td>';
    $html .= '<a class="btn btn-sm btn-info" href="' . e(url_for('admin/rooms/form.php?property_id=' . $id . '&id=' . $room['id'])) . '">Modifica</a> ';
    $html .= '<form method="post" style="display:inline" onsubmit="return confirm(\'Eliminare questa stanza?\')">';
    $html .= csrf_field();
    $html .= '<input type="hidden" name="action" value="delete_room">';
    $html .= '<input type="hidden" name="room_id" value="' . e($room['id']) . '">';
    $html .= '<button type="submit" class="btn btn-sm btn-danger">Elimina</button>';
    $html .= '</form>';
    $html .= '</td></tr>';
}

if ($rooms === []) {
    $html .= '<tr><td colspan="6" class="text-center">Nessuna stanza.</td></tr>';
}
$html .= '</tbody></table>';

$html .= '<div class="admin-toolbar" style="margin-top:1.5rem">';
$html .= '<h3>Immagini (' . count($images) . ')</h3>';
$html .= '</div>';
$html .= '<form class="admin-form" method="post" enctype="multipart/form-data">';
$html .= csrf_field();
$html .= '<input type="hidden" name="action" value="upload_image">';
$html .= '<label>Nuova immagine<input type="file" name="image" accept="image/jpeg,image/png,image/webp" required></label>';
$html .= '<label>Didascalia<input type="text" name="caption" maxlength="150"></label>';
$html .= '<label class="checkbox-label"><input type="checkbox" name="is_cover" value="1"> Imposta come copertina</label>';
$html .= '<div class="form-actions"><button class="button" type="submit">Carica immagine</button></div>';
$html .= '</form>';

if ($images === []) {
    $html .= '<p class="muted">Nessuna immagine caricata.</p>';
} else {
    $html .= '<div class="admin-media-grid">';
    foreach ($images as $image) {
        $html .= '<div class="admin-media-item">';
        $html .= '<img src="' . e(image_src($image['filename'])) . '" alt="' . e((string) ($image['caption'] ?? '')) . '">';
        $html .= '<p>' . e((string) ($image['caption'] ?? '')) . '</p>';
        $html .= $image['is_cover'] ? '<span class="badge badge-info">Cover</span>' : '';
        $html .= '<div class="actions">';
        if (!$image['is_cover']) {
            $html .= '<form method="post" style="display:inline">';
            $html .= csrf_field();
            $html .= '<input type="hidden" name="action" value="set_cover">';
            $html .= '<input type="hidden" name="image_id" value="' . e($image['id']) . '">';
            $html .= '<button class="btn-sm" type="submit">Cover</button>';
            $html .= '</form> ';
        }
        $html .= '<form method="post" style="display:inline" onsubmit="return confirm(\'Eliminare questa immagine?\')">';
        $html .= csrf_field();
        $html .= '<input type="hidden" name="action" value="delete_image">';
        $html .= '<input type="hidden" name="image_id" value="' . e($image['id']) . '">';
        $html .= '<button class="btn-sm btn-danger" type="submit">Elimina</button>';
        $html .= '</form>';
        $html .= '</div></div>';
    }
    $html .= '</div>';
}

$html .= '<div class="admin-toolbar" style="margin-top:1.5rem">';
$html .= '<h3>Distanze dai poli (' . count($poles) . ')</h3>';
$html .= '</div>';
$html .= '<p class="muted">In fase 2 queste distanze sono calcolate automaticamente dalla mappa usando l indirizzo dell annuncio.</p>';
$html .= '<table class="admin-table"><thead><tr><th>Polo</th><th>Minuti</th><th>Fonte</th></tr></thead><tbody>';
foreach ($poles as $pole) {
    $html .= '<tr>';
    $html .= '<td>' . e($pole['pole_name']) . '</td>';
    $html .= '<td>' . e($pole['distance_minutes']) . '</td>';
    $html .= '<td>Mappa - ' . e(transit_label((string) $pole['transit_type'])) . '</td>';
    $html .= '</tr>';
}
if ($poles === []) {
    $html .= '<tr><td colspan="3" class="text-center">Distanze automatiche non ancora disponibili.</td></tr>';
}
$html .= '</tbody></table>';

$html .= '<form class="admin-form" method="post">';
$html .= csrf_field();
$html .= '<input type="hidden" name="action" value="refresh_distances">';
$html .= '<div class="form-actions"><button class="button" type="submit">Ricalcola da indirizzo</button></div>';
$html .= '</form>';

render_page_backend('Dettaglio annuncio', $html, [], 'admin.properties.index');
