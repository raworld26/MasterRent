<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';

require_login();
require_service('landlord.home');

$user = current_user();
$uid = (int) $user['id'];
$propRepo = new PropertyRepository();
$roomRepo = new RoomRepository();
$amenityRepo = new AmenityRepository();

$propertyId = (int) query_str('property_id');
$prop = $propertyId > 0 ? $propRepo->find($propertyId) : null;

if ($prop === null || (int) $prop['landlord_id'] !== $uid) {
    http_response_code(403);
    render_page_frontend('Accesso negato',
        '<section class="panel empty-state"><h1>Accesso negato</h1></section>',
        ['body_class' => 'page-dashboard']);
    exit;
}

$roomId = (int) query_str('id');
$editing = $roomId > 0;
$room = null;

if ($editing) {
    $room = $roomRepo->find($roomId);
    if ($room === null || (int) $room['property_id'] !== $propertyId) {
        http_response_code(404);
        render_page_frontend('Stanza non trovata',
            '<section class="panel empty-state"><h1>Stanza non trovata</h1></section>',
            ['body_class' => 'page-dashboard']);
        exit;
    }
}

$propUrl = url_for('landlord/property.php?id=' . $propertyId);

/* ---- DELETE action ---- */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && post_str('action') === 'delete') {
    if ($editing && verify_csrf_token((string) ($_POST['csrf_token'] ?? ''), 'room_delete')) {
        $roomRepo->delete($roomId);
        set_flash('success', 'Stanza eliminata.');
    }
    redirect($propUrl);
}

/* ---- Form data ---- */
$selectedAmenities = $editing ? $roomRepo->amenityIds($roomId) : [];

$data = [
    'name'              => $room['name'] ?? '',
    'type'              => $room['type'] ?? 'single',
    'price_monthly'     => (int) ($room['price_monthly'] ?? 300),
    'deposit_months'    => (int) ($room['deposit_months'] ?? 2),
    'expenses_included' => (int) ($room['expenses_included'] ?? 0),
    'contract_type'     => $room['contract_type'] ?? 'transitorio',
    'is_available'      => (int) ($room['is_available'] ?? 1),
];
$error = '';

/* ---- POST save ---- */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && post_str('action') !== 'delete') {
    if (!verify_csrf_token((string) ($_POST['csrf_token'] ?? ''), 'room_form')) {
        $error = 'Sessione scaduta. Riprova.';
    } else {
        $priceRaw = post_str('price_monthly');
        $data = [
            'name'              => post_str('name'),
            'type'              => in_array(post_str('type'), ['single', 'double', 'bed_space', 'entire_apartment'], true) ? post_str('type') : 'single',
            'price_monthly'     => is_numeric($priceRaw) ? number_format((float) $priceRaw, 2, '.', '') : $priceRaw,
            'deposit_months'    => max(0, min(6, post_int('deposit_months', 2))),
            'expenses_included' => isset($_POST['expenses_included']) ? 1 : 0,
            'contract_type'     => post_str('contract_type', 'transitorio'),
            'is_available'      => isset($_POST['is_available']) ? 1 : 0,
        ];
        $selectedAmenities = array_map('intval', (array) ($_POST['amenities'] ?? []));

        if ($data['name'] === '') {
            $error = 'Il nome della stanza è obbligatorio.';
        } elseif ($priceRaw === '' || !is_numeric($priceRaw) || (float) $priceRaw < 0) {
            $error = 'Inserisci un prezzo mensile valido e non negativo.';
        } else {
            $payload = $data + ['property_id' => $propertyId];
            if ($editing) {
                $roomRepo->update($roomId, $data);
            } else {
                $roomId = $roomRepo->create($payload);
            }
            $roomRepo->setAmenities($roomId, $selectedAmenities);
            set_flash('success', $editing ? 'Stanza aggiornata.' : 'Stanza creata.');
            redirect($propUrl);
        }
    }
}

/* ---- Amenities checkboxes ---- */
$allAmenities = $amenityRepo->all();
$amenitiesHtml = '';
foreach ($allAmenities as $a) {
    $checked = in_array((int) $a['id'], $selectedAmenities, true) ? 'checked' : '';
    $icon = trim((string) ($a['icon'] ?? ''));
    $iconHtml = $icon !== '' ? '<span class="amenity-token">' . e(strtoupper($icon)) . '</span>' : '';
    $amenitiesHtml .= '<label class="check-item"><input type="checkbox" name="amenities[]" value="' . e($a['id']) . '" ' . $checked . '>'
        . '<span class="check-copy">' . $iconHtml . '<span class="check-title">' . e($a['name']) . '</span></span></label>';
}

/* ---- Contract type options ---- */
$contractTypes = [
    ['id' => 'transitorio', 'name' => 'Transitorio'],
    ['id' => 'studenti', 'name' => 'Studenti (3+2)'],
    ['id' => 'concordato', 'name' => 'Concordato (3+2)'],
    ['id' => 'libero', 'name' => 'Libero (4+4)'],
];

$actionUrl = url_for('landlord/room_form.php?property_id=' . $propertyId . ($editing ? '&id=' . $roomId : ''));

$content = render_template('frontend/room_form', [
    'back_url'          => e($propUrl),
    'back_label'        => e($prop['title']),
    'form_title'        => $editing ? 'Modifica stanza' : 'Nuova stanza',
    'submit_label'      => $editing ? 'Salva modifiche' : 'Crea stanza',
    'action_url'        => e($actionUrl),
    'csrf_field'        => csrf_field('room_form'),
    'error'             => $error === '' ? '' : '<div class="alert alert-danger">' . e($error) . '</div>',
    'name'              => e($data['name']),
    'type_options'      => select_options(room_type_options(), $data['type'], 'id', 'name'),
    'price_monthly'     => (string) $data['price_monthly'],
    'deposit_months'    => (string) $data['deposit_months'],
    'contract_options'  => select_options($contractTypes, $data['contract_type'], 'id', 'name'),
    'expenses_checked'  => $data['expenses_included'] ? 'checked' : '',
    'available_checked' => $data['is_available'] ? 'checked' : '',
    'amenities_html'    => $amenitiesHtml,
]);

render_page_frontend($editing ? 'Modifica stanza' : 'Nuova stanza', $content, ['body_class' => 'page-dashboard']);
