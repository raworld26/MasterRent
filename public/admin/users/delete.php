<?php
declare(strict_types=1);
require_once __DIR__ . "/../../../includes/bootstrap.php";
require_login();
require_service("admin.users.manage");
if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "POST") redirect(url_for("admin/users/index.php"));
if (!verify_csrf_token((string) ($_POST["csrf_token"] ?? ""), "delete_user")) {
    set_flash("danger", "Token CSRF non valido.");
    redirect(url_for("admin/users/index.php"));
}
$id = post_int("id");
if ($id === (int)($_SESSION["user_id"] ?? 0)) {
    set_flash("danger", "Non puoi eliminare il tuo account.");
} elseif ($id > 0 && user_find_admin($id) !== null) {
    user_soft_delete($id);
    set_flash("success", "Utente eliminato/disattivato.");
} else {
    set_flash("danger", "Utente non trovato.");
}
redirect(url_for("admin/users/index.php"));
