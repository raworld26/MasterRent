<?php
declare(strict_types=1);
require_once __DIR__ . "/../../../includes/bootstrap.php";
require_login();
require_service("admin.services.manage");
if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") redirect(url_for("admin/services/index.php"));
if (!verify_csrf_token((string) ($_POST["csrf_token"] ?? ""), "delete_service")) {
    set_flash("danger", "Token CSRF non valido.");
    redirect(url_for("admin/services/index.php"));
}
$id = post_int("id");
if ($id > 0 && service_find($id) !== null) {
    service_delete($id);
    set_flash("success", "Servizio eliminato.");
} else {
    set_flash("danger", "Servizio non trovato.");
}
redirect(url_for("admin/services/index.php"));
