<?php
declare(strict_types=1);
require_once __DIR__ . "/../../../includes/bootstrap.php";
require_login();
require_service("admin.groups.manage");
if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") redirect(url_for("admin/groups/index.php"));
if (!verify_csrf_token((string) ($_POST["csrf_token"] ?? ""), "delete_group")) {
    set_flash("danger", "Token CSRF non valido.");
    redirect(url_for("admin/groups/index.php"));
}
$id = post_int("id");
$row = group_find($id);
if ($row !== null) {
    if ((int)$row["is_system"]) {
        set_flash("danger", "Impossibile eliminare un gruppo di sistema.");
    } else {
        group_delete($id);
        set_flash("success", "Gruppo eliminato.");
    }
} else {
    set_flash("danger", "Gruppo non trovato.");
}
redirect(url_for("admin/groups/index.php"));
