<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/bootstrap.php';

require_login();
require_service('admin.properties.manage');

$propertyRepo = new PropertyRepository();
$roomRepo = new RoomRepository();
$geoRepo = new GeoRepository();
$userRepo = new UserRepository();

$id = (int) query_str('id');
$editing = $id > 0;
$property = null;

if ($editing) {
    $property = $propertyRepo->find($id);
    if ($property === null) {
        set_flash('danger', 'Annuncio non trovato.');
        redirect(url_for('admin/properties/index.php'));
    }
}

$data = [
    'landlord_id' => (int) ($property['landlord_id'] ?? 0),
    'neighborhood_id' => (int) ($property['neighborhood_id'] ?? 0),
    'title' => (string) ($property['title'] ?? ''),
    'description' => (string) ($property['description'] ?? ''),
    'address' => (string) ($property['address'] ?? ''),
    'house_number' => (string) ($property['house_number'] ?? ''),
    'postal_code' => (string) ($property['postal_code'] ?? '67100'),
    'total_rooms' => (int) ($property['total_rooms'] ?? 1),
    'has_elevator' => (int) ($property['has_elevator'] ?? 0),
    'heating_type' => (string) ($property['heating_type'] ?? 'autonomous'),
];
$initialPrice = '';

$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verify_csrf_token(post_str('csrf_token'), 'admin_property_form')) {
        $errors[] = 'Token CSRF non valido.';
    }

    $data = [
        'landlord_id' => post_int('landlord_id'),
        'neighborhood_id' => post_int('neighborhood_id'),
        'title' => post_str('title'),
        'description' => post_str('description'),
        'address' => post_str('address'),
        'house_number' => post_str('house_number'),
        'postal_code' => post_str('postal_code', '67100'),
        'total_rooms' => max(1, min(20, post_int('total_rooms', 1))),
        'has_elevator' => isset($_POST['has_elevator']) ? 1 : 0,
        'heating_type' => post_str('heating_type') === 'centralized' ? 'centralized' : 'autonomous',
    ];
    $initialPrice = post_str('price_monthly');

    if ($data['landlord_id'] <= 0) {
        $errors[] = 'Seleziona un proprietario.';
    }
    if ($data['neighborhood_id'] <= 0) {
        $errors[] = 'Seleziona un quartiere.';
    }
    if ($data['title'] === '') {
        $errors[] = 'Il titolo e obbligatorio.';
    }
    if ($data['address'] === '') {
        $errors[] = 'L indirizzo e obbligatorio.';
    }
    if (!$editing && ($initialPrice === '' || !is_numeric($initialPrice) || (float) $initialPrice < 0)) {
        $errors[] = 'Prezzo mensile non valido.';
    }

    if ($errors === []) {
        if ($editing) {
            $propertyRepo->update($id, [
                'neighborhood_id' => $data['neighborhood_id'],
                'title' => $data['title'],
                'description' => $data['description'],
                'address' => $data['address'],
                'house_number' => $data['house_number'],
                'postal_code' => $data['postal_code'],
                'total_rooms' => $data['total_rooms'],
                'has_elevator' => $data['has_elevator'],
                'heating_type' => $data['heating_type'],
            ]);

            if ((int) $property['landlord_id'] !== $data['landlord_id']) {
                db()->prepare('UPDATE properties SET landlord_id = :landlord_id WHERE id = :id')
                    ->execute(['landlord_id' => $data['landlord_id'], 'id' => $id]);
            }

            $distanceResult = (new MapDistanceService())->syncForProperty($data + ['id' => $id], null, true);
            set_flash('success', ($distanceResult['ok'] ?? false)
                ? 'Annuncio aggiornato. Distanze ricalcolate automaticamente.'
                : 'Annuncio aggiornato.');
            if (!($distanceResult['ok'] ?? false)) {
                set_flash('warning', (string) $distanceResult['message']);
            }
            redirect(url_for('admin/properties/view.php?id=' . $id));
        }

        $newId = $propertyRepo->create($data);
        $roomRepo->create([
            'property_id' => $newId,
            'name' => $data['title'],
            'type' => 'single',
            'price_monthly' => number_format((float) $initialPrice, 2, '.', ''),
            'deposit_months' => 2,
            'expenses_included' => 0,
            'contract_type' => 'transitorio',
            'is_available' => 1,
        ]);
        $distanceResult = (new MapDistanceService())->syncForProperty($data + ['id' => $newId], null, true);
        set_flash('success', ($distanceResult['ok'] ?? false)
            ? 'Annuncio creato con una soluzione iniziale. Distanze calcolate automaticamente.'
            : 'Annuncio creato con una soluzione iniziale.');
        if (!($distanceResult['ok'] ?? false)) {
            set_flash('warning', (string) $distanceResult['message']);
        }
        redirect(url_for('admin/properties/view.php?id=' . $newId));
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

$landlords = $userRepo->landlordsForSelect();
$neighborhoods = $geoRepo->allNeighborhoods();
$title = $editing ? 'Modifica annuncio' : 'Nuovo annuncio';
$action = $editing ? url_for('admin/properties/form.php?id=' . $id) : url_for('admin/properties/form.php');

$html .= '<form class="admin-form" method="post" action="' . e($action) . '">';
$html .= csrf_field('admin_property_form');

$html .= '<label>Proprietario<select name="landlord_id" required>'
    . select_options($landlords, $data['landlord_id'], 'id', 'name', 'Seleziona proprietario')
    . '</select></label>';
$html .= '<label>Quartiere<select name="neighborhood_id" required>'
    . select_options($neighborhoods, $data['neighborhood_id'], 'id', 'name', 'Seleziona quartiere')
    . '</select></label>';
$html .= '<label>Titolo<input type="text" name="title" value="' . e($data['title']) . '" required></label>';
$html .= '<label>Descrizione<textarea name="description" rows="5">' . e($data['description']) . '</textarea></label>';
$html .= '<label>Indirizzo<input type="text" name="address" value="' . e($data['address']) . '" required></label>';
$html .= '<label>Numero civico<input type="text" name="house_number" value="' . e($data['house_number']) . '"></label>';
$html .= '<label>CAP<input type="text" name="postal_code" value="' . e($data['postal_code']) . '"></label>';
$html .= '<label>Stanze totali<input type="number" name="total_rooms" min="1" max="20" value="' . e((string) $data['total_rooms']) . '"></label>';
if (!$editing) {
    $html .= '<label>Prezzo mensile iniziale (&euro;)<input type="number" name="price_monthly" min="0" step="0.01" value="' . e($initialPrice) . '" required></label>';
}
$html .= '<label>Riscaldamento<select name="heating_type">'
    . select_options([
        ['id' => 'autonomous', 'name' => 'Autonomo'],
        ['id' => 'centralized', 'name' => 'Centralizzato'],
    ], $data['heating_type'], 'id', 'name')
    . '</select></label>';
$html .= '<label class="checkbox-label"><input type="checkbox" name="has_elevator" value="1"'
    . ($data['has_elevator'] ? ' checked' : '') . '> Ascensore presente</label>';

$html .= '<div class="form-actions">';
$html .= '<button class="button" type="submit">' . e($editing ? 'Salva modifiche' : 'Crea annuncio') . '</button> ';
$html .= '<a href="' . e($editing ? url_for('admin/properties/view.php?id=' . $id) : url_for('admin/properties/index.php')) . '">Annulla</a>';
$html .= '</div>';
$html .= '</form>';

render_page_backend($title, $html, [], 'admin.properties.index');
