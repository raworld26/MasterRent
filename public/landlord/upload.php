<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';

require_login();
require_service('landlord.home');

$user = current_user();
$uid = (int) $user['id'];
$propRepo = new PropertyRepository();

$propertyId = (int) query_str('property_id');
$prop = $propertyId > 0 ? $propRepo->find($propertyId) : null;

if ($prop === null || (int) $prop['landlord_id'] !== $uid) {
    http_response_code(403);
    render_page_frontend('Accesso negato',
        '<section class="panel empty-state"><h1>Accesso negato</h1></section>',
        ['body_class' => 'page-dashboard']);
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

$file = $_FILES['image'] ?? null;
if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    set_flash('danger', 'Nessun file caricato o errore durante l\'upload.');
    redirect($backUrl);
}

if ($file['size'] > UPLOAD_MAX_BYTES) {
    set_flash('danger', 'Il file supera il limite di 4 MB.');
    redirect($backUrl);
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
$allowedMimes = explode(',', UPLOAD_ALLOWED_MIME);
if (!in_array($mime, $allowedMimes, true)) {
    set_flash('danger', 'Formato non supportato. Usa JPEG, PNG o WebP.');
    redirect($backUrl);
}

$ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? 'jpg';
$filename = 'prop_' . $propertyId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;

if (!is_dir(UPLOADS_DIR)) {
    mkdir(UPLOADS_DIR, 0755, true);
}

$dest = UPLOADS_DIR . '/' . $filename;
if (!move_uploaded_file($file['tmp_name'], $dest)) {
    set_flash('danger', 'Errore nel salvataggio del file.');
    redirect($backUrl);
}

$isCover = isset($_POST['is_cover']);
$caption = post_str('caption') ?: null;

$propRepo->addImage($propertyId, $filename, $isCover, $caption);
set_flash('success', 'Immagine caricata.');
redirect($backUrl);
