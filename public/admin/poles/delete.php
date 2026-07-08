<?php
declare(strict_types=1);
require_once __DIR__ . "/../../../includes/bootstrap.php";
require_login();
require_service("admin.poles.manage");
if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") redirect(url_for("admin/poles/index.php"));
if (!verify_csrf_token((string) ($_POST["csrf_token"] ?? ""), "delete_pole")) {
    set_flash("danger", "Token CSRF non valido.");
    redirect(url_for("admin/poles/index.php"));
}
$id = post_int("id");
if ($id > 0 && pole_find($id) !== null) {
    if (pole_in_use($id)) {
        set_flash("danger", "Impossibile eliminare: polo in uso da almeno un annuncio.");
    } else {
        pole_delete($id);
        set_flash("success", "Polo eliminato.");
    }
} else {
    set_flash("danger", "Polo non trovato.");
}
redirect(url_for("admin/poles/index.php"));
