<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';

require_login();
require_service('landlord.home');

$user = current_user();
$uid = (int) $user['id'];

$propertyId = (int) query_str('property_id');
$prop = $propertyId > 0 ? property_find($propertyId) : null;

if ($prop === null || (int) $prop['landlord_id'] !== $uid) {
    http_response_code(403);
    render_page('Accesso negato', '<section class="panel empty-state"><h1>Accesso negato</h1></section>', ['body_class' => 'page-dashboard']);
    exit;
}

$roomId = (int) query_str('id');
$editing = $roomId > 0;
$room = null;

if ($editing) {
    $room = room_find($roomId);
    if ($room === null || (int) $room['property_id'] !== $propertyId) {
        http_response_code(404);
        render_page('Stanza non trovata', '<section class="panel empty-state"><h1>Stanza non trovata</h1></section>', ['body_class' => 'page-dashboard']);
        exit;
    }
}

$propUrl = url_for('landlord/property.php?id=' . $propertyId);

/* ---- DELETE ---- */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && post_str('action') === 'delete') {
    if ($editing && verify_csrf_token((string) ($_POST['csrf_token'] ?? ''), 'room_delete')) {
        room_delete($roomId);
        property_refresh_room_count($propertyId);
        set_flash('success', 'Stanza eliminata.');
    }
    redirect($propUrl);
}

$selectedAmenities = $editing ? room_amenity_ids($roomId) : [];
$data = [
    'name' => (string) ($room['name'] ?? ''),
    'type' => (string) ($room['type'] ?? 'single'),
    'price_monthly' => (int) ($room['price_monthly'] ?? 300),
    'deposit_months' => (int) ($room['deposit_months'] ?? 1),
    'expenses_included' => (int) ($room['expenses_included'] ?? 0),
    'contract_type' => (string) ($room['contract_type'] ?? 'Transitorio Studenti'),
    'is_available' => (int) ($room['is_available'] ?? 1),
];
$error = '';

/* ---- SAVE ---- */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && post_str('action') !== 'delete') {
    if (!verify_csrf_token((string) ($_POST['csrf_token'] ?? ''), 'room_form')) {
        $error = 'Sessione scaduta. Riprova.';
    } else {
        $type = post_str('type');
        $priceRaw = post_str('price_monthly');
        $data = [
            'name' => post_str('name'),
            'type' => in_array($type, ['single', 'double', 'bed_space', 'entire_apartment'], true) ? $type : 'single',
            'price_monthly' => is_numeric($priceRaw) ? number_format((float) $priceRaw, 2, '.', '') : $priceRaw,
            'deposit_months' => max(0, min(6, post_int('deposit_months', 1))),
            'expenses_included' => isset($_POST['expenses_included']) ? 1 : 0,
            'contract_type' => post_str('contract_type', 'Transitorio Studenti'),
            'is_available' => isset($_POST['is_available']) ? 1 : 0,
        ];
        $selectedAmenities = array_map('intval', (array) ($_POST['amenities'] ?? []));

        if ($data['name'] === '') {
            $error = 'Il nome della stanza è obbligatorio.';
        } elseif ($priceRaw === '' || !is_numeric($priceRaw) || (float) $priceRaw < 0) {
            $error = 'Inserisci un prezzo mensile valido e non negativo.';
        } else {
            if ($editing) {
                room_update($roomId, $data);
                $savedId = $roomId;
            } else {
                $savedId = room_create($data + ['property_id' => $propertyId]);
            }
            room_set_amenities($savedId, $selectedAmenities);
            property_refresh_room_count($propertyId);
            set_flash('success', $editing ? 'Stanza aggiornata.' : 'Stanza creata.');
            redirect($propUrl);
        }
    }
}

$typeItems = room_type_options();
$contractItems = [
    ['id' => 'Transitorio Studenti', 'name' => 'Transitorio Studenti'],
    ['id' => 'Studenti (3+2)', 'name' => 'Studenti (3+2)'],
    ['id' => 'Concordato (3+2)', 'name' => 'Concordato (3+2)'],
    ['id' => 'Libero (4+4)', 'name' => 'Libero (4+4)'],
];

$amenitiesHtml = '';
foreach (amenities_all() as $a) {
    $amenitiesHtml .= '<label class="check-row"><input type="checkbox" name="amenities[]" value="' . e((string) $a['id']) . '"'
        . checked_attr(in_array((int) $a['id'], $selectedAmenities, true)) . '> ' . e($a['name']) . '</label>';
}

$actionUrl = url_for('landlord/room_form.php?property_id=' . $propertyId . ($editing ? '&id=' . $roomId : ''));

$html = '<section class="dashboard-shell">'
    . '<header class="dashboard-header"><div><p class="eyebrow">' . e($prop['title']) . '</p><h1>' . ($editing ? 'Modifica stanza' : 'Nuova stanza') . '</h1></div>'
    . '<a class="button-secondary" href="' . e($propUrl) . '">Annulla</a></header>'
    . ($error !== '' ? '<div class="alert alert-danger">' . e($error) . '</div>' : '')
    . '<section class="panel form-panel"><form class="form-standard" method="post" action="' . e($actionUrl) . '">'
    . csrf_field('room_form')
    . '<div class="form-group"><label>Nome stanza</label><input type="text" name="name" maxlength="100" value="' . e($data['name']) . '" required></div>'
    . '<div class="form-grid">'
    . '<div class="form-group"><label>Tipologia</label><select name="type">' . select_options($typeItems, $data['type'], 'id', 'name') . '</select></div>'
    . '<div class="form-group"><label>Prezzo mensile (€)</label><input type="number" name="price_monthly" min="50" max="5000" step="10" value="' . e((string) $data['price_monthly']) . '" required></div>'
    . '<div class="form-group"><label>Mesi di caparra</label><input type="number" name="deposit_months" min="0" max="6" step="1" value="' . e((string) $data['deposit_months']) . '"></div>'
    . '</div>'
    . '<div class="form-group"><label>Tipo contratto</label><select name="contract_type">' . select_options($contractItems, $data['contract_type'], 'id', 'name') . '</select></div>'
    . '<div class="form-grid">'
    . '<div class="form-group"><label class="check-row"><input type="checkbox" name="expenses_included" value="1"' . checked_attr((bool) $data['expenses_included']) . '> Spese incluse</label></div>'
    . '<div class="form-group"><label class="check-row"><input type="checkbox" name="is_available" value="1"' . checked_attr((bool) $data['is_available']) . '> Disponibile nella ricerca</label></div>'
    . '</div>'
    . '<div class="form-group"><label>Accessori</label><div class="check-list">' . $amenitiesHtml . '</div></div>'
    . '<div class="form-actions"><button type="submit" class="button-primary">' . ($editing ? 'Salva modifiche' : 'Crea stanza') . '</button></div>'
    . '</form></section></section>';

$html = str_replace('name="price_monthly" min="50" max="5000" step="10"', 'name="price_monthly" min="0" max="5000" step="0.01"', $html);

render_page($editing ? 'Modifica stanza' : 'Nuova stanza', $html, ['body_class' => 'page-dashboard']);
