<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';

require_login();
require_service('landlord.home');

$user = current_user();
$uid = (int) $user['id'];

$id = (int) query_str('id');
$editing = $id > 0;
$prop = null;

if ($editing) {
    $prop = property_find($id);
    if ($prop === null || (int) $prop['landlord_id'] !== $uid) {
        http_response_code(403);
        render_page('Accesso negato', '<section class="panel empty-state"><h1>Accesso negato</h1></section>', ['body_class' => 'page-dashboard']);
        exit;
    }
}

$data = [
    'title' => (string) ($prop['title'] ?? ''),
    'neighborhood_id' => (int) ($prop['neighborhood_id'] ?? 0),
    'address' => (string) ($prop['address'] ?? ''),
    'house_number' => (string) ($prop['house_number'] ?? ''),
    'postal_code' => (string) ($prop['postal_code'] ?? '67100'),
    'total_rooms' => (int) ($prop['total_rooms'] ?? 1),
    'description' => (string) ($prop['description'] ?? ''),
    'heating_type' => (string) ($prop['heating_type'] ?? 'autonomous'),
    'has_elevator' => (int) ($prop['has_elevator'] ?? 0),
];
$initialPrice = '';
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $data = [
        'title' => post_str('title'),
        'neighborhood_id' => post_int('neighborhood_id'),
        'address' => post_str('address'),
        'house_number' => post_str('house_number'),
        'postal_code' => post_str('postal_code', '67100'),
        'total_rooms' => max(1, min(20, post_int('total_rooms', 1))),
        'description' => post_str('description'),
        'heating_type' => post_str('heating_type', 'autonomous') === 'centralized' ? 'centralized' : 'autonomous',
        'has_elevator' => isset($_POST['has_elevator']) ? 1 : 0,
    ];
    $initialPrice = post_str('price_monthly');

    if (!verify_csrf_token((string) ($_POST['csrf_token'] ?? ''), 'property_form')) {
        $error = 'Sessione scaduta. Riprova.';
    } elseif ($data['title'] === '' || $data['address'] === '') {
        $error = 'Titolo e indirizzo sono obbligatori.';
    } elseif ($data['neighborhood_id'] <= 0) {
        $error = 'Seleziona un quartiere.';
    } elseif (!$editing && ($initialPrice === '' || !is_numeric($initialPrice) || (float) $initialPrice < 0)) {
        $error = 'Inserisci un prezzo mensile valido e non negativo.';
    } else {
        if ($editing) {
            property_update($id, $data);
            $targetId = $id;
        } else {
            $targetId = property_create($data + ['landlord_id' => $uid]);
            room_create([
                'property_id' => $targetId,
                'name' => $data['title'],
                'type' => 'single',
                'price_monthly' => number_format((float) $initialPrice, 2, '.', ''),
                'deposit_months' => 2,
                'expenses_included' => 0,
                'contract_type' => 'Transitorio Studenti',
                'is_available' => 1,
            ]);
            property_refresh_room_count($targetId);
        }

        // Upload multiplo immagini (facoltativo).
        $saved = 0;
        $files = $_FILES['images'] ?? null;
        if (is_array($files) && is_array($files['name'] ?? null)) {
            $hasCover = property_cover($targetId) !== null;
            foreach ($files['name'] as $i => $name) {
                $one = [
                    'name' => (string) $name,
                    'tmp_name' => (string) ($files['tmp_name'][$i] ?? ''),
                    'size' => (int) ($files['size'][$i] ?? 0),
                    'error' => (int) ($files['error'][$i] ?? UPLOAD_ERR_NO_FILE),
                ];
                try {
                    $img = save_property_image_upload($one, $targetId);
                } catch (RuntimeException $e) {
                    set_flash('warning', $e->getMessage());
                    continue;
                }
                if ($img !== null) {
                    $isCover = !$hasCover && $saved === 0;
                    property_add_image($targetId, $img['filename'], $isCover, $img['caption']);
                    $saved++;
                    if ($isCover) {
                        $hasCover = true;
                    }
                }
            }
        }

        $msg = $editing ? 'Annuncio aggiornato.' : 'Annuncio creato con una soluzione iniziale. Ora puoi gestire stanze, foto e distanze.';
        if ($saved > 0) {
            $msg .= ' Foto caricate: ' . $saved . '.';
        }
        set_flash('success', $msg);
        redirect(url_for('landlord/property.php?id=' . $targetId));
    }
}

$heatingItems = [['id' => 'autonomous', 'name' => 'Autonomo'], ['id' => 'centralized', 'name' => 'Centralizzato']];

$html = '<section class="dashboard-shell">'
    . '<header class="dashboard-header"><div><p class="eyebrow">Area Proprietario</p><h1>' . ($editing ? 'Modifica annuncio' : 'Nuovo annuncio') . '</h1></div>'
    . '<a class="button-secondary" href="' . e($editing ? url_for('landlord/property.php?id=' . $id) : url_for('landlord/index.php')) . '">Annulla</a></header>'
    . ($error !== '' ? '<div class="alert alert-danger">' . e($error) . '</div>' : '')
    . '<section class="panel form-panel"><form class="form-standard" method="post" enctype="multipart/form-data" action="'
    . e($editing ? url_for('landlord/property_form.php?id=' . $id) : url_for('landlord/property_form.php')) . '">'
    . csrf_field('property_form')
    . '<div class="form-group"><label>Titolo annuncio</label><input type="text" name="title" maxlength="150" value="' . e($data['title']) . '" required></div>'
    . '<div class="form-grid">'
    . '<div class="form-group"><label>Quartiere</label><select name="neighborhood_id" required>' . select_options(neighborhoods_all(), $data['neighborhood_id'], 'id', 'name', 'Seleziona quartiere') . '</select></div>'
    . '<div class="form-group"><label>Stanze totali</label><input type="number" name="total_rooms" min="1" max="20" value="' . e((string) $data['total_rooms']) . '"></div>'
    . '</div>'
    . (!$editing ? '<div class="form-grid"><div class="form-group"><label>Prezzo mensile iniziale (&euro;)</label><input type="number" name="price_monthly" min="0" step="0.01" value="' . e($initialPrice) . '" required></div></div>' : '')
    . '<div class="form-grid">'
    . '<div class="form-group"><label>Indirizzo</label><input type="text" name="address" maxlength="190" value="' . e($data['address']) . '" required></div>'
    . '<div class="form-group"><label>N. civico</label><input type="text" name="house_number" maxlength="20" value="' . e($data['house_number']) . '"></div>'
    . '<div class="form-group"><label>CAP</label><input type="text" name="postal_code" maxlength="10" value="' . e($data['postal_code']) . '"></div>'
    . '</div>'
    . '<div class="form-group"><label>Descrizione</label><textarea name="description" rows="5">' . e($data['description']) . '</textarea></div>'
    . '<div class="form-grid">'
    . '<div class="form-group"><label>Riscaldamento</label><select name="heating_type">' . select_options($heatingItems, $data['heating_type'], 'id', 'name') . '</select></div>'
    . '<div class="form-group"><label class="check-row"><input type="checkbox" name="has_elevator" value="1"' . checked_attr((bool) $data['has_elevator']) . '> Ascensore presente</label></div>'
    . '</div>'
    . '<div class="form-group"><label>Foto (una o più, JPG/PNG/WebP max 4 MB)</label><input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple></div>'
    . '<div class="form-actions"><button type="submit" class="button-primary">' . ($editing ? 'Salva modifiche' : 'Crea annuncio') . '</button></div>'
    . '</form></section></section>';

render_page($editing ? 'Modifica annuncio' : 'Nuovo annuncio', $html, ['body_class' => 'page-dashboard']);
