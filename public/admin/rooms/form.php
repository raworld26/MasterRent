<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/bootstrap.php';

require_login();
require_service('admin.properties.manage');

$propertyRepo = new PropertyRepository();
$roomRepo = new RoomRepository();
$amenityRepo = new AmenityRepository();

$propertyId = post_int('property_id', (int) query_str('property_id'));
$property = $propertyId > 0 ? $propertyRepo->find($propertyId) : null;

if ($property === null) {
    set_flash('danger', 'Annuncio non trovato.');
    redirect(url_for('admin/properties/index.php'));
}

$roomId = (int) query_str('id');
$editing = $roomId > 0;
$room = null;

if ($editing) {
    $room = $roomRepo->find($roomId);
    if ($room === null || (int) $room['property_id'] !== $propertyId) {
        set_flash('danger', 'Stanza non trovata.');
        redirect(url_for('admin/properties/view.php?id=' . $propertyId));
    }
}

$selectedAmenities = $editing ? $roomRepo->amenityIds($roomId) : [];
$data = [
    'name' => (string) ($room['name'] ?? ''),
    'type' => (string) ($room['type'] ?? 'single'),
    'price_monthly' => (int) ($room['price_monthly'] ?? 300),
    'deposit_months' => (int) ($room['deposit_months'] ?? 2),
    'expenses_included' => (int) ($room['expenses_included'] ?? 0),
    'contract_type' => (string) ($room['contract_type'] ?? 'transitorio'),
    'status' => (string) ($room['status'] ?? 'available'),
];
$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verify_csrf_token(post_str('csrf_token'), 'admin_room_form')) {
        $errors[] = 'Token CSRF non valido.';
    }

    $type = post_str('type');
    $contract = post_str('contract_type', 'transitorio');
    $allowedContracts = ['transitorio', 'studenti', 'concordato', 'libero'];
    $status = post_str('status', 'available');
    if (!in_array($status, ['available', 'reserved', 'unavailable'], true)) {
        $status = 'available';
    }
    $priceRaw = post_str('price_monthly');

    $data = [
        'name' => post_str('name'),
        'type' => in_array($type, ['single', 'double', 'bed_space', 'entire_apartment'], true) ? $type : 'single',
        'price_monthly' => is_numeric($priceRaw) ? number_format((float) $priceRaw, 2, '.', '') : $priceRaw,
        'deposit_months' => max(0, min(6, post_int('deposit_months', 2))),
        'expenses_included' => isset($_POST['expenses_included']) ? 1 : 0,
        'contract_type' => in_array($contract, $allowedContracts, true) ? $contract : 'transitorio',
        'is_available' => $status === 'available' ? 1 : 0,
        'status' => $status,
    ];
    $selectedAmenities = array_map('intval', (array) ($_POST['amenities'] ?? []));

    if ($data['name'] === '') {
        $errors[] = 'Il nome della stanza e obbligatorio.';
    }
    if ($priceRaw === '' || !is_numeric($priceRaw) || (float) $priceRaw < 0) {
        $errors[] = 'Prezzo mensile non valido.';
    }

    if ($errors === []) {
        if ($editing) {
            $roomRepo->update($roomId, [
                'name' => $data['name'],
                'type' => $data['type'],
                'price_monthly' => $data['price_monthly'],
                'deposit_months' => $data['deposit_months'],
                'expenses_included' => $data['expenses_included'],
                'contract_type' => $data['contract_type'],
                'is_available' => $data['is_available'],
            ]);
            $savedId = $roomId;
            set_flash('success', 'Stanza aggiornata.');
        } else {
            $savedId = $roomRepo->create([
                'property_id' => $propertyId,
                'name' => $data['name'],
                'type' => $data['type'],
                'price_monthly' => $data['price_monthly'],
                'deposit_months' => $data['deposit_months'],
                'expenses_included' => $data['expenses_included'],
                'contract_type' => $data['contract_type'],
                'is_available' => $data['is_available'],
            ]);
            set_flash('success', 'Stanza creata.');
        }
        $roomRepo->setStatus($savedId, $data['status']);
        $roomRepo->setAmenities($savedId, $selectedAmenities);
        redirect(url_for('admin/properties/view.php?id=' . $propertyId));
    }
}

$html = '';
if ($errors !== []) {
    $html .= '<div class="alert alert-danger"><ul>';
    foreach ($errors as $error) {
        $html .= '<li>' . e($error) . '</li>';
    }
    $html .= '</ul></div>';
}

$amenitiesHtml = '<div class="admin-check-grid">';
foreach ($amenityRepo->all() as $amenity) {
    $checked = in_array((int) $amenity['id'], $selectedAmenities, true) ? ' checked' : '';
    $amenitiesHtml .= '<label class="checkbox-label"><input type="checkbox" name="amenities[]" value="'
        . e((string) $amenity['id']) . '"' . $checked . '> ' . e($amenity['name']) . '</label>';
}
$amenitiesHtml .= '</div>';

$title = $editing ? 'Modifica stanza' : 'Nuova stanza';
$action = url_for('admin/rooms/form.php?property_id=' . $propertyId . ($editing ? '&id=' . $roomId : ''));

$html .= '<p class="muted">Annuncio: <strong>' . e($property['title']) . '</strong></p>';
$html .= '<form class="admin-form" method="post" action="' . e($action) . '">';
$html .= csrf_field('admin_room_form');
$html .= '<input type="hidden" name="property_id" value="' . e((string) $propertyId) . '">';

$html .= '<label>Nome stanza<input type="text" name="name" value="' . e($data['name']) . '" required></label>';
$html .= '<label>Tipologia<select name="type">' . select_options(room_type_options(), $data['type'], 'id', 'name') . '</select></label>';
$html .= '<label>Prezzo mensile<input type="number" name="price_monthly" min="0" max="5000" step="0.01" value="' . e((string) $data['price_monthly']) . '" required></label>';
$html .= '<label>Mesi di cauzione<input type="number" name="deposit_months" min="0" max="6" step="1" value="' . e((string) $data['deposit_months']) . '" required></label>';
$html .= '<label>Tipo contratto<select name="contract_type">' . select_options([
    ['id' => 'transitorio', 'name' => 'Transitorio'],
    ['id' => 'studenti', 'name' => 'Studenti (3+2)'],
    ['id' => 'concordato', 'name' => 'Concordato (3+2)'],
    ['id' => 'libero', 'name' => 'Libero (4+4)'],
], $data['contract_type'], 'id', 'name') . '</select></label>';
$html .= '<label>Stato<select name="status">' . select_options([
    ['id' => 'available', 'name' => 'Disponibile'],
    ['id' => 'reserved', 'name' => 'Prenotata'],
    ['id' => 'unavailable', 'name' => 'Non disponibile'],
], $data['status'], 'id', 'name') . '</select></label>';
$html .= '<label class="checkbox-label"><input type="checkbox" name="expenses_included" value="1"'
    . ($data['expenses_included'] ? ' checked' : '') . '> Spese incluse</label>';
$html .= '<fieldset class="form-group"><legend>Accessori stanza</legend>' . $amenitiesHtml . '</fieldset>';

$html .= '<div class="form-actions">';
$html .= '<button class="button" type="submit">' . e($editing ? 'Salva modifiche' : 'Crea stanza') . '</button> ';
$html .= '<a href="' . e(url_for('admin/properties/view.php?id=' . $propertyId)) . '">Annulla</a>';
$html .= '</div>';
$html .= '</form>';

render_page_backend($title, $html, [], 'admin.properties.index');
