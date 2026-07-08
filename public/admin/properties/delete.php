<?php
declare(strict_types=1);
require_once __DIR__ . "/../../../includes/bootstrap.php";
require_login();
require_service("admin.properties.manage");
if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") redirect(url_for("admin/properties/index.php"));
if (!verify_csrf_token((string) ($_POST["csrf_token"] ?? ""), "delete_property")) {
    set_flash("danger", "Token CSRF non valido.");
    redirect(url_for("admin/properties/index.php"));
}
$id = post_int("id");
if ($id > 0 && property_find($id) !== null) {
    foreach (property_images($id) as $img) {
        delete_uploaded_image_file($img["filename"]);
    }
    property_delete($id);
    set_flash("success", "Annuncio eliminato.");
} else {
    set_flash("danger", "Annuncio non trovato.");
}
redirect(url_for("admin/properties/index.php"));
