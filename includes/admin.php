<?php

declare(strict_types=1);

function slug_code(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?? '';
    return trim($value, '_');
}

function selected_attr($current, $value): string
{
    return (string) $current === (string) $value ? ' selected' : '';
}

function checked_attr(bool $checked): string
{
    return $checked ? ' checked' : '';
}

function admin_flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function admin_flash_html(): string
{
    $items = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);

    $html = '';
    foreach ($items as $item) {
        $type = ($item['type'] ?? '') === 'success' ? 'success' : 'danger';
        $html .= '<div class="alert alert-' . $type . '">' . e($item['message'] ?? '') . '</div>';
    }
    return $html;
}

function admin_groups(): array
{
    return db()->query('SELECT id, code, name, is_system FROM user_groups ORDER BY name ASC')->fetchAll();
}

function admin_service_group_ids(int $serviceId): array
{
    $stmt = db()->prepare('SELECT group_id FROM services_has_groups WHERE service_id = :id');
    $stmt->execute(['id' => $serviceId]);
    return array_map('intval', array_column($stmt->fetchAll(), 'group_id'));
}

function admin_user_group_ids(int $userId): array
{
    $stmt = db()->prepare('SELECT group_id FROM users_has_groups WHERE user_id = :id');
    $stmt->execute(['id' => $userId]);
    return array_map('intval', array_column($stmt->fetchAll(), 'group_id'));
}

function user_form_fields(string $email, string $firstName, string $lastName, string $phone, string $status, array $selectedGroups, bool $passwordRequired): string
{
    $groups = '';
    foreach (admin_groups() as $group) {
        $id = (int) $group['id'];
        $groups .= '<label class="check-row"><input type="checkbox" name="groups[]" value="' . e((string) $id) . '"' . checked_attr(in_array($id, $selectedGroups, true)) . '> ' . e($group['name']) . '</label>';
    }

    return '<div class="form-grid">'
        . '<div class="form-group"><label>Email</label><input type="email" name="email" value="' . e($email) . '" required></div>'
        . '<div class="form-group"><label>Password' . ($passwordRequired ? '' : ' (lascia vuota per non cambiare)') . '</label><input type="password" name="password"' . ($passwordRequired ? ' required' : '') . '></div>'
        . '<div class="form-group"><label>Nome</label><input type="text" name="first_name" value="' . e($firstName) . '" required></div>'
        . '<div class="form-group"><label>Cognome</label><input type="text" name="last_name" value="' . e($lastName) . '" required></div>'
        . '<div class="form-group"><label>Telefono</label><input type="text" name="phone" value="' . e($phone) . '"></div>'
        . '<div class="form-group"><label>Stato</label><select name="status">'
        . '<option value="active"' . selected_attr($status, 'active') . '>active</option>'
        . '<option value="pending"' . selected_attr($status, 'pending') . '>pending</option>'
        . '<option value="disabled"' . selected_attr($status, 'disabled') . '>disabled</option>'
        . '</select></div>'
        . '</div>'
        . '<div class="form-group"><label>Gruppi</label><div class="check-list">' . $groups . '</div></div>';
}

function group_form_fields(string $code, string $name, string $description, bool $codeReadonly): string
{
    return '<div class="form-group"><label>Codice</label><input type="text" name="code" value="' . e($code) . '"' . ($codeReadonly ? ' readonly' : '') . '></div>'
        . '<div class="form-group"><label>Nome gruppo</label><input type="text" name="name" value="' . e($name) . '" required></div>'
        . '<div class="form-group"><label>Descrizione</label><textarea name="description" rows="5">' . e($description) . '</textarea></div>';
}

function amenity_form_fields(string $code, string $name, string $icon, bool $codeReadonly): string
{
    return '<div class="form-grid">'
        . '<div class="form-group"><label>Codice</label><input type="text" name="code" value="' . e($code) . '"' . ($codeReadonly ? ' readonly' : '') . ' placeholder="wifi"></div>'
        . '<div class="form-group"><label>Nome accessorio</label><input type="text" name="name" value="' . e($name) . '" required placeholder="Wi-Fi"></div>'
        . '<div class="form-group"><label>Icona</label><input type="text" name="icon" value="' . e($icon) . '" placeholder="wifi"></div>'
        . '</div>';
}

function admin_review_room_options(int $selectedRoomId): string
{
    $rows = db()->query(
        'SELECT r.id, r.name, p.title AS property_title
         FROM rooms AS r
         JOIN properties AS p ON p.id = r.property_id
         ORDER BY p.title ASC, r.name ASC'
    )->fetchAll();

    $options = '<option value="">Seleziona stanza</option>';
    foreach ($rows as $row) {
        $label = '#' . $row['id'] . ' - ' . $row['name'] . ' / ' . $row['property_title'];
        $options .= '<option value="' . e((string) $row['id']) . '"' . selected_attr($selectedRoomId, $row['id']) . '>' . e($label) . '</option>';
    }

    return $options;
}

function admin_review_student_options(int $selectedUserId): string
{
    $rows = db()->query(
        'SELECT DISTINCT u.id, u.email, u.first_name, u.last_name
         FROM users AS u
         JOIN users_has_groups AS uhg ON uhg.user_id = u.id
         JOIN user_groups AS g ON g.id = uhg.group_id
         WHERE g.code = "student"
           AND u.status = "active"
           AND u.deleted_at IS NULL
         ORDER BY u.last_name ASC, u.first_name ASC, u.email ASC'
    )->fetchAll();

    $options = '<option value="">Seleziona studente</option>';
    foreach ($rows as $row) {
        $name = trim($row['first_name'] . ' ' . $row['last_name']);
        $label = '#' . $row['id'] . ' - ' . ($name === '' ? $row['email'] : $name) . ' (' . $row['email'] . ')';
        $options .= '<option value="' . e((string) $row['id']) . '"' . selected_attr($selectedUserId, $row['id']) . '>' . e($label) . '</option>';
    }

    return $options;
}

function review_form_fields(int $roomId, int $userId, int $rating, string $title, string $body, bool $isPublic): string
{
    return '<div class="form-grid">'
        . '<div class="form-group"><label>Stanza</label><select name="room_id" required>' . admin_review_room_options($roomId) . '</select></div>'
        . '<div class="form-group"><label>Studente</label><select name="user_id" required>' . admin_review_student_options($userId) . '</select></div>'
        . '<div class="form-group"><label>Valutazione</label><input type="number" name="rating" min="1" max="5" step="1" value="' . e((string) $rating) . '" required></div>'
        . '<div class="form-group"><label>Titolo</label><input type="text" name="title" maxlength="120" value="' . e($title) . '" required></div>'
        . '</div>'
        . '<div class="form-group"><label>Testo recensione</label><textarea name="body" rows="6" required>' . e($body) . '</textarea></div>'
        . '<label class="check-row"><input type="checkbox" name="is_public" value="1"' . checked_attr($isPublic) . '> Pubblica nel dettaglio annuncio</label>';
}

function booking_request_form_fields(
    int $roomId,
    int $studentId,
    string $status,
    string $visitDate,
    string $message,
    string $moveInDate = '',
    string $depositAmount = '',
    string $depositPaidAt = '',
    string $paymentReference = ''
): string
{
    $paymentFields = '';
    if ($depositAmount !== '' || $depositPaidAt !== '' || $paymentReference !== '') {
        $paymentFields = '<div class="form-group"><label>Dati pagamento simulato</label><p class="muted">'
            . e('Caparra: ' . ($depositAmount === '' ? '-' : format_price($depositAmount))
                . ' - Rif. ' . ($paymentReference === '' ? '-' : $paymentReference)
                . ' - Pagata il ' . ($depositPaidAt === '' ? '-' : $depositPaidAt))
            . '</p></div>';
    }

    return '<div class="form-grid">'
        . '<div class="form-group"><label>Stanza</label><select name="room_id" required>' . admin_review_room_options($roomId) . '</select></div>'
        . '<div class="form-group"><label>Studente</label><select name="student_id" required>' . admin_review_student_options($studentId) . '</select></div>'
        . '</div>'
        . '<div class="form-grid">'
        . '<div class="form-group"><label>Stato</label><select name="status">'
        . booking_request_status_options($status)
        . '</select></div>'
        . '<div class="form-group"><label>Data visita</label><input type="date" name="visit_date" value="' . e($visitDate) . '"></div>'
        . '<div class="form-group"><label>Data ingresso indicativa</label><input type="date" name="move_in_date" value="' . e($moveInDate) . '"></div>'
        . '</div>'
        . '<div class="form-group"><label>Messaggio iniziale</label><textarea name="message" rows="5" maxlength="2000">' . e($message) . '</textarea></div>'
        . $paymentFields;
}

function landlord_id_for_room(int $roomId): int
{
    $stmt = db()->prepare(
        'SELECT p.landlord_id
         FROM rooms AS r
         JOIN properties AS p ON p.id = r.property_id
         WHERE r.id = :room_id'
    );
    $stmt->execute(['room_id' => $roomId]);
    return (int) ($stmt->fetchColumn() ?: 0);
}

function admin_landlord_options(int $selectedUserId): string
{
    $rows = db()->query(
        'SELECT DISTINCT u.id, u.email, u.first_name, u.last_name
         FROM users AS u
         JOIN users_has_groups AS uhg ON uhg.user_id = u.id
         JOIN user_groups AS g ON g.id = uhg.group_id
         WHERE g.code = "landlord"
           AND u.status = "active"
           AND u.deleted_at IS NULL
         ORDER BY u.last_name ASC, u.first_name ASC, u.email ASC'
    )->fetchAll();

    $options = '<option value="">Seleziona proprietario</option>';
    foreach ($rows as $row) {
        $name = trim($row['first_name'] . ' ' . $row['last_name']);
        $label = '#' . $row['id'] . ' - ' . ($name === '' ? $row['email'] : $name) . ' (' . $row['email'] . ')';
        $options .= '<option value="' . e((string) $row['id']) . '"' . selected_attr($selectedUserId, $row['id']) . '>' . e($label) . '</option>';
    }

    return $options;
}

function admin_neighborhood_options(int $selectedNeighborhoodId): string
{
    $rows = db()->query('SELECT id, name FROM neighborhoods ORDER BY name ASC')->fetchAll();

    $options = '<option value="">Seleziona quartiere</option>';
    foreach ($rows as $row) {
        $options .= '<option value="' . e((string) $row['id']) . '"' . selected_attr($selectedNeighborhoodId, $row['id']) . '>' . e($row['name']) . '</option>';
    }

    return $options;
}

function property_form_fields(
    int $landlordId,
    int $neighborhoodId,
    string $title,
    string $description,
    string $address,
    string $houseNumber,
    string $postalCode,
    string $heatingType,
    bool $hasElevator
): string {
    return '<div class="form-grid">'
        . '<div class="form-group"><label>Proprietario</label><select name="landlord_id" required>' . admin_landlord_options($landlordId) . '</select></div>'
        . '<div class="form-group"><label>Quartiere</label><select name="neighborhood_id" required>' . admin_neighborhood_options($neighborhoodId) . '</select></div>'
        . '</div>'
        . '<div class="form-group"><label>Titolo annuncio</label><input type="text" name="title" maxlength="150" value="' . e($title) . '" required></div>'
        . '<div class="form-grid">'
        . '<div class="form-group"><label>Indirizzo</label><input type="text" name="address" maxlength="190" value="' . e($address) . '" required></div>'
        . '<div class="form-group"><label>N. civico</label><input type="text" name="house_number" maxlength="20" value="' . e($houseNumber) . '"></div>'
        . '<div class="form-group"><label>CAP</label><input type="text" name="postal_code" maxlength="10" value="' . e($postalCode) . '" required></div>'
        . '</div>'
        . '<div class="form-group"><label>Descrizione</label><textarea name="description" rows="6">' . e($description) . '</textarea></div>'
        . '<div class="form-grid">'
        . '<div class="form-group"><label>Riscaldamento</label><select name="heating_type">'
        . '<option value="autonomous"' . selected_attr($heatingType, 'autonomous') . '>Autonomo</option>'
        . '<option value="centralized"' . selected_attr($heatingType, 'centralized') . '>Centralizzato</option>'
        . '</select></div>'
        . '<label class="check-row"><input type="checkbox" name="has_elevator" value="1"' . checked_attr($hasElevator) . '> Ascensore presente</label>'
        . '</div>';
}

function admin_property_options(int $selectedPropertyId): string
{
    $rows = db()->query(
        'SELECT p.id, p.title, p.address, u.first_name, u.last_name
         FROM properties AS p
         JOIN users AS u ON u.id = p.landlord_id
         ORDER BY p.title ASC, p.address ASC'
    )->fetchAll();

    $options = '<option value="">Seleziona alloggio</option>';
    foreach ($rows as $row) {
        $landlord = trim($row['first_name'] . ' ' . $row['last_name']);
        $label = '#' . $row['id'] . ' - ' . $row['title'] . ' / ' . $row['address'] . ' / ' . $landlord;
        $options .= '<option value="' . e((string) $row['id']) . '"' . selected_attr($selectedPropertyId, $row['id']) . '>' . e($label) . '</option>';
    }

    return $options;
}

function property_image_form_fields(int $propertyId, string $caption, bool $isCover, bool $uploadRequired, ?string $filename = null): string
{
    $currentImage = '';
    if ($filename !== null && $filename !== '') {
        $currentImage = '<div class="form-group"><label>Immagine attuale</label>'
            . property_image_markup($filename, $caption === '' ? 'Immagine annuncio' : $caption, 'admin-thumb')
            . '<p class="muted">' . e($filename) . '</p></div>';
    }

    return $currentImage
        . '<div class="form-group"><label>Alloggio</label><select name="property_id" required>' . admin_property_options($propertyId) . '</select></div>'
        . '<div class="form-grid">'
        . '<div class="form-group"><label>File immagine</label><input type="file" name="image_file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"' . ($uploadRequired ? ' required' : '') . '><small>JPG, PNG o WebP. Massimo 4 MB.</small></div>'
        . '<div class="form-group"><label>Didascalia</label><input type="text" name="caption" maxlength="150" value="' . e($caption) . '"></div>'
        . '</div>'
        . '<label class="check-row"><input type="checkbox" name="is_cover" value="1"' . checked_attr($isCover) . '> Imposta come copertina dell\'annuncio</label>';
}

function admin_pole_options(int $selectedPoleId): string
{
    $rows = db()->query('SELECT id, name FROM university_poles ORDER BY name ASC')->fetchAll();

    $options = '<option value="">Seleziona polo</option>';
    foreach ($rows as $row) {
        $options .= '<option value="' . e((string) $row['id']) . '"' . selected_attr($selectedPoleId, $row['id']) . '>' . e($row['name']) . '</option>';
    }

    return $options;
}

function property_pole_form_fields(int $propertyId, int $poleId, string $distanceMinutes, string $transitType): string
{
    return '<div class="form-grid">'
        . '<div class="form-group"><label>Alloggio</label><select name="property_id" required>' . admin_property_options($propertyId) . '</select></div>'
        . '<div class="form-group"><label>Polo universitario</label><select name="pole_id" required>' . admin_pole_options($poleId) . '</select></div>'
        . '</div>'
        . '<div class="form-grid">'
        . '<div class="form-group"><label>Minuti distanza</label><input type="number" name="distance_minutes" min="1" max="180" step="1" value="' . e($distanceMinutes) . '" required></div>'
        . '<div class="form-group"><label>Mezzo</label><select name="transit_type">'
        . '<option value="foot"' . selected_attr($transitType, 'foot') . '>A piedi</option>'
        . '<option value="bus"' . selected_attr($transitType, 'bus') . '>Bus</option>'
        . '<option value="car"' . selected_attr($transitType, 'car') . '>Auto</option>'
        . '</select></div>'
        . '</div>';
}

function set_property_image_cover(int $propertyId, int $imageId): void
{
    $db = db();
    $stmt = $db->prepare('UPDATE property_images SET is_cover = 0 WHERE property_id = :property_id');
    $stmt->execute(['property_id' => $propertyId]);

    $stmt = $db->prepare('UPDATE property_images SET is_cover = 1 WHERE id = :id AND property_id = :property_id');
    $stmt->execute(['id' => $imageId, 'property_id' => $propertyId]);
}

function property_has_cover_image(int $propertyId): bool
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM property_images WHERE property_id = :property_id AND is_cover = 1');
    $stmt->execute(['property_id' => $propertyId]);
    return (int) $stmt->fetchColumn() > 0;
}

function ensure_property_cover(int $propertyId): void
{
    if ($propertyId <= 0 || property_has_cover_image($propertyId)) {
        return;
    }

    $stmt = db()->prepare(
        'SELECT id
         FROM property_images
         WHERE property_id = :property_id
         ORDER BY id ASC
         LIMIT 1'
    );
    $stmt->execute(['property_id' => $propertyId]);
    $imageId = (int) ($stmt->fetchColumn() ?: 0);
    if ($imageId > 0) {
        set_property_image_cover($propertyId, $imageId);
    }
}

function delete_property_generated_image_file(?string $filename): void
{
    $relativePath = ltrim(str_replace('\\', '/', trim((string) $filename)), '/');
    if ($relativePath === '' || str_contains($relativePath, '..') || str_contains($relativePath, '/')) {
        return;
    }

    if (!str_starts_with($relativePath, 'prop_')) {
        return;
    }

    $path = PROJECT_ROOT . '/public/assets/uploads/' . $relativePath;
    if (is_file($path)) {
        unlink($path);
    }
}

function admin_room_amenity_ids(int $roomId): array
{
    if ($roomId <= 0) {
        return [];
    }

    $stmt = db()->prepare('SELECT amenity_id FROM room_has_amenities WHERE room_id = :room_id');
    $stmt->execute(['room_id' => $roomId]);
    return array_map('intval', array_column($stmt->fetchAll(), 'amenity_id'));
}

function admin_room_amenities_checklist(array $selectedAmenities): string
{
    $rows = db()->query('SELECT id, name FROM amenities ORDER BY name ASC')->fetchAll();

    if ($rows === []) {
        return '<p class="muted">Nessun accessorio configurato.</p>';
    }

    $html = '';
    foreach ($rows as $row) {
        $id = (int) $row['id'];
        $html .= '<label class="check-row"><input type="checkbox" name="amenities[]" value="' . e((string) $id) . '"' . checked_attr(in_array($id, $selectedAmenities, true)) . '> ' . e($row['name']) . '</label>';
    }

    return $html;
}

function room_form_fields(
    int $propertyId,
    string $name,
    string $type,
    string $priceMonthly,
    int $depositMonths,
    string $contractType,
    bool $expensesIncluded,
    bool $isAvailable,
    array $selectedAmenities
): string {
    return '<div class="form-group"><label>Alloggio</label><select name="property_id" required>' . admin_property_options($propertyId) . '</select></div>'
        . '<div class="form-grid">'
        . '<div class="form-group"><label>Nome stanza</label><input type="text" name="name" maxlength="100" value="' . e($name) . '" required></div>'
        . '<div class="form-group"><label>Tipologia</label><select name="type">'
        . '<option value="single"' . selected_attr($type, 'single') . '>Singola</option>'
        . '<option value="double"' . selected_attr($type, 'double') . '>Doppia</option>'
        . '<option value="bed_space"' . selected_attr($type, 'bed_space') . '>Posto letto</option>'
        . '<option value="entire_apartment"' . selected_attr($type, 'entire_apartment') . '>Intero appartamento</option>'
        . '</select></div>'
        . '</div>'
        . '<div class="form-grid">'
        . '<div class="form-group"><label>Prezzo mensile</label><input type="number" name="price_monthly" min="1" step="0.01" value="' . e($priceMonthly) . '" required></div>'
        . '<div class="form-group"><label>Mesi caparra</label><input type="number" name="deposit_months" min="0" max="6" step="1" value="' . e((string) $depositMonths) . '" required></div>'
        . '<div class="form-group"><label>Contratto</label><input type="text" name="contract_type" maxlength="100" value="' . e($contractType) . '" required></div>'
        . '</div>'
        . '<div class="form-grid">'
        . '<label class="check-row"><input type="checkbox" name="expenses_included" value="1"' . checked_attr($expensesIncluded) . '> Spese incluse</label>'
        . '<label class="check-row"><input type="checkbox" name="is_available" value="1"' . checked_attr($isAvailable) . '> Disponibile nella ricerca</label>'
        . '</div>'
        . '<div class="form-group"><label>Accessori stanza</label><div class="choice-list">' . admin_room_amenities_checklist($selectedAmenities) . '</div></div>';
}

function save_room_amenities(int $roomId, array $amenityIds): void
{
    $db = db();
    $stmt = $db->prepare('DELETE FROM room_has_amenities WHERE room_id = :room_id');
    $stmt->execute(['room_id' => $roomId]);

    if ($amenityIds === []) {
        return;
    }

    $stmt = $db->prepare('INSERT INTO room_has_amenities (room_id, amenity_id) VALUES (:room_id, :amenity_id)');
    foreach (array_unique(array_map('intval', $amenityIds)) as $amenityId) {
        if ($amenityId > 0) {
            $stmt->execute(['room_id' => $roomId, 'amenity_id' => $amenityId]);
        }
    }
}

function refresh_property_room_count(int $propertyId): void
{
    if ($propertyId <= 0) {
        return;
    }

    $stmt = db()->prepare(
        'UPDATE properties
         SET total_rooms = (SELECT COUNT(*) FROM rooms WHERE property_id = :count_property_id)
         WHERE id = :target_property_id'
    );
    $stmt->execute([
        'count_property_id' => $propertyId,
        'target_property_id' => $propertyId,
    ]);
}

function service_form_fields(array $data, array $selectedGroups): string
{
    $groups = '';
    foreach (admin_groups() as $group) {
        $id = (int) $group['id'];
        $groups .= '<label class="check-row"><input type="checkbox" name="groups[]" value="' . e((string) $id) . '"' . checked_attr(in_array($id, $selectedGroups, true)) . '> ' . e($group['name']) . '</label>';
    }

    return '<div class="form-grid">'
        . '<div class="form-group"><label>Codice servizio</label><input type="text" name="code" value="' . e($data['code'] ?? '') . '" required></div>'
        . '<div class="form-group"><label>Nome</label><input type="text" name="name" value="' . e($data['name'] ?? '') . '" required></div>'
        . '<div class="form-group"><label>Area</label><select name="area">'
        . '<option value="auth"' . selected_attr($data['area'] ?? '', 'auth') . '>auth</option>'
        . '<option value="frontend"' . selected_attr($data['area'] ?? '', 'frontend') . '>frontend</option>'
        . '<option value="backend"' . selected_attr($data['area'] ?? '', 'backend') . '>backend</option>'
        . '</select></div>'
        . '<div class="form-group"><label>Metodo</label><select name="http_method">'
        . '<option value="ALL"' . selected_attr($data['http_method'] ?? '', 'ALL') . '>ALL</option>'
        . '<option value="GET"' . selected_attr($data['http_method'] ?? '', 'GET') . '>GET</option>'
        . '<option value="POST"' . selected_attr($data['http_method'] ?? '', 'POST') . '>POST</option>'
        . '</select></div>'
        . '<div class="form-group"><label>Path</label><input type="text" name="path" value="' . e($data['path'] ?? '') . '" placeholder="/admin/users/index.php"></div>'
        . '<div class="form-group"><label>Ordine menu</label><input type="number" name="menu_order" value="' . e((string) ($data['menu_order'] ?? 0)) . '"></div>'
        . '</div>'
        . '<div class="form-group"><label>Descrizione</label><textarea name="description" rows="4">' . e($data['description'] ?? '') . '</textarea></div>'
        . '<div class="form-grid">'
        . '<label class="check-row"><input type="checkbox" name="is_menu_item" value="1"' . checked_attr(!empty($data['is_menu_item'])) . '> Mostra nel menu admin</label>'
        . '<label class="check-row"><input type="checkbox" name="is_active" value="1"' . checked_attr((int) ($data['is_active'] ?? 1) === 1) . '> Servizio attivo</label>'
        . '</div>'
        . '<div class="form-group"><label>Gruppi abilitati</label><div class="check-list">' . $groups . '</div></div>';
}

function service_post_data(): array
{
    return [
        'code' => slug_code((string) ($_POST['code'] ?? '')),
        'name' => trim((string) ($_POST['name'] ?? '')),
        'description' => trim((string) ($_POST['description'] ?? '')) ?: null,
        'area' => in_array($_POST['area'] ?? '', ['auth', 'frontend', 'backend'], true) ? $_POST['area'] : 'backend',
        'path' => trim((string) ($_POST['path'] ?? '')) ?: null,
        'http_method' => in_array($_POST['http_method'] ?? '', ['ALL', 'GET', 'POST'], true) ? $_POST['http_method'] : 'ALL',
        'is_menu_item' => isset($_POST['is_menu_item']) ? 1 : 0,
        'menu_order' => max(0, (int) ($_POST['menu_order'] ?? 0)),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];
}

function save_service_groups(int $serviceId, array $groupIds): void
{
    $db = db();
    $stmt = $db->prepare('DELETE FROM services_has_groups WHERE service_id = :id');
    $stmt->execute(['id' => $serviceId]);
    $stmt = $db->prepare('INSERT INTO services_has_groups (service_id, group_id) VALUES (:service_id, :group_id)');
    foreach ($groupIds as $groupId) {
        $stmt->execute(['service_id' => $serviceId, 'group_id' => $groupId]);
    }
}
