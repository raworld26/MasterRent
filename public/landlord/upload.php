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

$backUrl = url_for('landlord/property.php?id=' . $propertyId);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirect($backUrl);
}

if (!verify_csrf_token((string) ($_POST['csrf_token'] ?? ''), 'prop_upload')) {
    set_flash('danger', 'Sessione scaduta. Riprova.');
    redirect($backUrl);
}

try {
    $img = save_property_image_upload($_FILES['image'] ?? [], $propertyId);
} catch (RuntimeException $e) {
    set_flash('danger', $e->getMessage());
    redirect($backUrl);
}

if ($img === null) {
    set_flash('danger', 'Nessun file caricato.');
    redirect($backUrl);
}

$caption = post_str('caption');
$isCover = isset($_POST['is_cover']) || property_cover($propertyId) === null;
$imageId = property_add_image($propertyId, $img['filename'], false, $caption !== '' ? $caption : $img['caption']);
if ($isCover) {
    property_set_cover($propertyId, $imageId);
}

set_flash('success', 'Immagine caricata.');
redirect($backUrl);
