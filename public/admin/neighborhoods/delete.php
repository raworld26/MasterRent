<?php
declare(strict_types=1);
require_once __DIR__ . "/../../../includes/bootstrap.php";
require_login();
require_service("admin.neighborhoods.manage");
if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") redirect(url_for("admin/neighborhoods/index.php"));
if (!verify_csrf_token((string) ($_POST["csrf_token"] ?? ""), "delete_neighborhood")) {
    set_flash("danger", "Token CSRF non valido.");
    redirect(url_for("admin/neighborhoods/index.php"));
}
$id = post_int("id");
if ($id > 0 && neighborhood_find($id) !== null) {
    if (neighborhood_in_use($id)) {
        set_flash("danger", "Impossibile eliminare: quartiere in uso da almeno un annuncio.");
    } else {
        neighborhood_delete($id);
        set_flash("success", "Quartiere eliminato.");
    }
} else {
    set_flash("danger", "Quartiere non trovato.");
}
redirect(url_for("admin/neighborhoods/index.php"));
