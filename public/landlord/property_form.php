<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';

require_login();
require_service('landlord.home');

$user = current_user();
$uid = (int) $user['id'];
$repo = new PropertyRepository();
$roomRepo = new RoomRepository();
$amenityRepo = new AmenityRepository();
$geo = new GeoRepository();

function property_form_uploaded_images(): array
{
    $files = $_FILES['images'] ?? null;
    if (!is_array($files) || !is_array($files['name'] ?? null)) {
        return [];
    }

    $uploads = [];
    foreach ($files['name'] as $i => $name) {
        $error = (int) ($files['error'][$i] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE && trim((string) $name) === '') {
            continue;
        }

        $uploads[] = [
            'name' => (string) $name,
            'tmp_name' => (string) ($files['tmp_name'][$i] ?? ''),
            'size' => (int) ($files['size'][$i] ?? 0),
            'error' => $error,
        ];
    }

    return $uploads;
}

function property_form_prepare_images(array $uploads, array &$errors): array
{
    $prepared = [];
    if ($uploads === []) {
        return $prepared;
    }

    $allowedMimes = explode(',', UPLOAD_ALLOWED_MIME);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);

    foreach ($uploads as $upload) {
        if ((int) $upload['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Una foto non è stata caricata correttamente.';
            continue;
        }

        if ((int) $upload['size'] > UPLOAD_MAX_BYTES) {
            $errors[] = 'Una foto supera il limite di 4 MB.';
            continue;
        }

        $tmpName = (string) $upload['tmp_name'];
        $mime = $tmpName !== '' ? $finfo->file($tmpName) : false;
        if (!is_string($mime) || !in_array($mime, $allowedMimes, true)) {
            $errors[] = 'Una foto ha un formato non supportato.';
            continue;
        }

        $prepared[] = [
            'tmp_name' => $tmpName,
            'ext' => $extensions[$mime] ?? 'jpg',
            'caption' => mb_substr(pathinfo((string) $upload['name'], PATHINFO_FILENAME), 0, 150),
        ];
    }

    return $prepared;
}

function property_form_save_images(PropertyRepository $repo, int $propertyId, array $images, array &$errors): int
{
    if ($images === []) {
        return 0;
    }

    if (!is_dir(UPLOADS_DIR)) {
        mkdir(UPLOADS_DIR, 0755, true);
    }

    $saved = 0;
    $hasCover = $repo->coverFor($propertyId) !== null;

    foreach ($images as $image) {
        $filename = 'prop_' . $propertyId . '_' . bin2hex(random_bytes(8)) . '.' . $image['ext'];
        $dest = UPLOADS_DIR . '/' . $filename;
        if (!move_uploaded_file((string) $image['tmp_name'], $dest)) {
            $errors[] = 'Una foto non è stata salvata.';
            continue;
        }

        $isCover = !$hasCover && $saved === 0;
        $caption = trim((string) $image['caption']);
        $repo->addImage($propertyId, $filename, $isCover, $caption !== '' ? $caption : null);
        $saved++;
        if ($isCover) {
            $hasCover = true;
        }
    }

    return $saved;
}

$id = (int) query_str('id');
$editing = $id > 0;
$prop = null;

if ($editing) {
    $prop = $repo->find($id);
    if ($prop === null || (int) $prop['landlord_id'] !== $uid) {
        http_response_code(403);
        render_page_frontend('Accesso negato', '<section class="panel empty-state"><h1>Accesso negato</h1></section>', ['body_class' => 'page-dashboard']);
        exit;
    }
}

$data = [
    'title' => $prop['title'] ?? '',
    'neighborhood_id' => (int) ($prop['neighborhood_id'] ?? 0),
    'address' => $prop['address'] ?? '',
    'house_number' => $prop['house_number'] ?? '',
    'postal_code' => $prop['postal_code'] ?? '67100',
    'total_rooms' => (int) ($prop['total_rooms'] ?? 1),
    'description' => $prop['description'] ?? '',
    'heating_type' => $prop['heating_type'] ?? 'autonomous',
    'has_elevator' => (int) ($prop['has_elevator'] ?? 0),
];
$initialPrice = '';
$error = '';

// Accessori: in modifica pre-seleziona l'unione degli accessori di tutte le stanze dell'immobile.
$selectedAmenities = [];
if ($editing) {
    foreach ($roomRepo->byProperty($id) as $r) {
        foreach ($roomRepo->amenityIds((int) $r['id']) as $aid) {
            $selectedAmenities[$aid] = $aid;
        }
    }
    $selectedAmenities = array_values($selectedAmenities);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $data = [
        'title' => post_str('title'),
        'neighborhood_id' => post_int('neighborhood_id'),
        'address' => post_str('address'),
        'house_number' => post_str('house_number'),
        'postal_code' => post_str('postal_code', '67100'),
        'total_rooms' => max(1, post_int('total_rooms', 1)),
        'description' => post_str('description'),
        'heating_type' => post_str('heating_type', 'autonomous') === 'centralized' ? 'centralized' : 'autonomous',
        'has_elevator' => isset($_POST['has_elevator']) ? 1 : 0,
    ];
    $initialPrice = post_str('price_monthly');
    $selectedAmenities = array_map('intval', (array) ($_POST['amenities'] ?? []));
    $uploadErrors = [];
    $preparedImages = property_form_prepare_images(property_form_uploaded_images(), $uploadErrors);

    if (!verify_csrf_token((string) ($_POST['csrf_token'] ?? ''), 'property_form')) {
        $error = 'Sessione scaduta. Riprova.';
    } elseif ($data['title'] === '' || $data['address'] === '') {
        $error = 'Titolo e indirizzo sono obbligatori.';
    } elseif ($data['neighborhood_id'] <= 0) {
        $error = 'Seleziona un quartiere.';
    } elseif (!$editing && ($initialPrice === '' || !is_numeric($initialPrice) || (float) $initialPrice < 0)) {
        $error = 'Inserisci un prezzo mensile valido e non negativo.';
    } elseif ($uploadErrors !== []) {
        $error = implode(' ', array_unique($uploadErrors));
    } else {
        $payload = $data + ['landlord_id' => $uid];
        $saveErrors = [];
        if ($editing) {
            $repo->update($id, $data);
            foreach ($roomRepo->byProperty($id) as $r) {
                $roomRepo->setAmenities((int) $r['id'], $selectedAmenities);
            }
            $distanceResult = (new MapDistanceService())->syncForProperty($data + ['id' => $id], null, true);
            $savedImages = property_form_save_images($repo, $id, $preparedImages, $saveErrors);
            $message = 'Annuncio aggiornato.';
            if ($distanceResult['ok'] ?? false) {
                $message .= ' Distanze ricalcolate automaticamente.';
            }
            if ($savedImages > 0) {
                $message .= ' Foto caricate: ' . $savedImages . '.';
            }
            set_flash('success', $message);
            if (!($distanceResult['ok'] ?? false)) {
                set_flash('warning', (string) $distanceResult['message']);
            }
            if ($saveErrors !== []) {
                set_flash('warning', implode(' ', array_unique($saveErrors)));
            }
            redirect(url_for('landlord/property.php?id=' . $id));
        } else {
            $newId = $repo->create($payload);
            $newRoomId = $roomRepo->create([
                'property_id' => $newId,
                'name' => $data['title'],
                'type' => 'single',
                'price_monthly' => number_format((float) $initialPrice, 2, '.', ''),
                'deposit_months' => 2,
                'expenses_included' => 0,
                'contract_type' => 'transitorio',
                'is_available' => 1,
            ]);
            $roomRepo->setAmenities($newRoomId, $selectedAmenities);
            $distanceResult = (new MapDistanceService())->syncForProperty($payload + ['id' => $newId], null, true);
            $savedImages = property_form_save_images($repo, $newId, $preparedImages, $saveErrors);
            $message = $savedImages > 0
                ? 'Annuncio creato con una soluzione iniziale e ' . $savedImages . ' foto.'
                : 'Annuncio creato con una soluzione iniziale. Ora puoi gestire stanze e foto.';
            if ($distanceResult['ok'] ?? false) {
                $message .= ' Distanze calcolate automaticamente.';
            }
            set_flash('success', $message);
            if (!($distanceResult['ok'] ?? false)) {
                set_flash('warning', (string) $distanceResult['message']);
            }
            if ($saveErrors !== []) {
                set_flash('warning', implode(' ', array_unique($saveErrors)));
            }
            redirect(url_for('landlord/property.php?id=' . $newId));
        }
    }
}

/* ---- Amenities checkboxes (valgono per tutte le stanze dell'immobile) ---- */
$amenitiesHtml = '';
foreach ($amenityRepo->all() as $a) {
    $checked = in_array((int) $a['id'], $selectedAmenities, true) ? 'checked' : '';
    $icon = trim((string) ($a['icon'] ?? ''));
    $iconHtml = $icon !== '' ? '<span class="amenity-token">' . e(strtoupper($icon)) . '</span>' : '';
    $amenitiesHtml .= '<label class="check-item"><input type="checkbox" name="amenities[]" value="' . e($a['id']) . '" ' . $checked . '>'
        . '<span class="check-copy">' . $iconHtml . '<span class="check-title">' . e($a['name']) . '</span></span></label>';
}

$content = render_template('frontend/property_form', [
    'back_url' => e(url_for('landlord/index.php')),
    'back_label' => 'I miei annunci',
    'form_title' => $editing ? 'Modifica annuncio' : 'Nuovo annuncio',
    'submit_label' => $editing ? 'Salva modifiche' : 'Crea annuncio',
    'action_url' => e($editing ? url_for('landlord/property_form.php?id=' . $id) : url_for('landlord/property_form.php')),
    'csrf_field' => csrf_field('property_form'),
    'error' => $error === '' ? '' : '<div class="alert alert-danger">' . e($error) . '</div>',
    'title' => e($data['title']),
    'neighborhood_options' => select_options($geo->allNeighborhoods(), $data['neighborhood_id'], 'id', 'name', 'Seleziona quartiere'),
    'address' => e($data['address']),
    'house_number' => e($data['house_number']),
    'postal_code' => e($data['postal_code']),
    'total_rooms' => (string) $data['total_rooms'],
    'initial_price_field' => !$editing
        ? '<label class="field"><span class="field-label">Prezzo mensile iniziale (&euro;)</span><input type="number" name="price_monthly" value="' . e($initialPrice) . '" min="0" step="0.01" required></label>'
        : '',
    'description' => e($data['description']),
    'heating_options' => select_options([
        ['id' => 'autonomous', 'name' => 'Autonomo'],
        ['id' => 'centralized', 'name' => 'Centralizzato'],
    ], $data['heating_type'], 'id', 'name'),
    'elevator_checked' => $data['has_elevator'] ? 'checked' : '',
    'amenities_html' => $amenitiesHtml,
]);

render_page_frontend($editing ? 'Modifica annuncio' : 'Nuovo annuncio', $content, ['body_class' => 'page-dashboard']);
