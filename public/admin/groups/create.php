<?php
declare(strict_types=1);
require_once __DIR__ . "/../../../includes/bootstrap.php";
require_login();
require_service("admin.groups.manage");
$id = (int) query_str("id");
$edit = $id > 0;
$data = ["code" => "", "name" => "", "description" => ""];
if ($edit) {
    $row = group_find($id);
    if ($row === null) {
        set_flash("danger", "Gruppo non trovato.");
        redirect(url_for("admin/groups/index.php"));
    }
    $data = $row;
}
$errors = [];
if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") {
    if (!verify_csrf_token((string) ($_POST["csrf_token"] ?? ""), "group_form")) {
        $errors[] = "Token CSRF non valido.";
    }
    $data["code"] = post_str("code");
    $data["name"] = post_str("name");
    $data["description"] = post_str("description");

    if ($data["code"] === "") $errors[] = "Il codice è obbligatorio.";
    if ($data["name"] === "") $errors[] = "Il nome è obbligatorio.";
    if ($data["code"] !== "" && group_code_exists($data["code"], $edit ? $id : 0)) {
        $errors[] = "Il codice è già in uso.";
    }

    if ($errors === []) {
        if ($edit) {
            group_update($id, $data["code"], $data["name"], $data["description"]);
            set_flash("success", "Gruppo aggiornato.");
        } else {
            group_create($data["code"], $data["name"], $data["description"]);
            set_flash("success", "Gruppo creato.");
        }
        redirect(url_for("admin/groups/index.php"));
    }
}
$actionUrl = $edit ? url_for("admin/groups/create.php?id=" . $id) : url_for("admin/groups/create.php");
$html = "<div class=\"admin-toolbar\"><h1>" . ($edit ? "Modifica gruppo" : "Nuovo gruppo") . "</h1></div>";
if ($errors !== []) {
    $html .= "<div class=\"alert alert-danger\"><ul>";
    foreach ($errors as $err) $html .= "<li>" . e($err) . "</li>";
    $html .= "</ul></div>";
}
$html .= "<section class=\"panel form-panel\"><form class=\"admin-form\" method=\"post\" action=\"" . e($actionUrl) . "\">"
    . csrf_field("group_form")
    . "<label>Codice<input type=\"text\" name=\"code\" value=\"" . e((string) $data["code"]) . "\" required></label>"
    . "<label>Nome<input type=\"text\" name=\"name\" value=\"" . e((string) $data["name"]) . "\" required></label>"
    . "<label>Descrizione<textarea name=\"description\" rows=\"3\">" . e((string) $data["description"]) . "</textarea></label>"
    . "<div class=\"form-actions\"><button class=\"button-primary\" type=\"submit\">" . ($edit ? "Salva modifiche" : "Crea gruppo") . "</button> "
    . "<a href=\"" . e(url_for("admin/groups/index.php")) . "\">Annulla</a></div>"
    . "</form></section>";
render_admin_page($edit ? "Modifica gruppo" : "Nuovo gruppo", $html, "admin.groups.index");
