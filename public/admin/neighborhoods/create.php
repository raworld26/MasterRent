<?php
declare(strict_types=1);
require_once __DIR__ . "/../../../includes/bootstrap.php";
require_login();
require_service("admin.neighborhoods.manage");
$id = (int) query_str("id");
$edit = $id > 0;
$data = ["code" => "", "name" => "", "description" => ""];
if ($edit) {
    $row = neighborhood_find($id);
    if ($row === null) {
        set_flash("danger", "Quartiere non trovato.");
        redirect(url_for("admin/neighborhoods/index.php"));
    }
    $data = $row;
}
$errors = [];
if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") {
    if (!verify_csrf_token((string) ($_POST["csrf_token"] ?? ""), "neighborhood_form")) {
        $errors[] = "Token CSRF non valido.";
    }
    $data["code"] = post_str("code");
    $data["name"] = post_str("name");
    $data["description"] = post_str("description");

    if ($data["code"] === "") $errors[] = "Il codice è obbligatorio.";
    if ($data["name"] === "") $errors[] = "Il nome è obbligatorio.";
    if ($data["code"] !== "" && neighborhood_code_exists($data["code"], $edit ? $id : 0)) {
        $errors[] = "Il codice è già in uso.";
    }

    if ($errors === []) {
        if ($edit) {
            neighborhood_update($id, $data["code"], $data["name"], $data["description"]);
            set_flash("success", "Quartiere aggiornato.");
        } else {
            neighborhood_create($data["code"], $data["name"], $data["description"]);
            set_flash("success", "Quartiere creato.");
        }
        redirect(url_for("admin/neighborhoods/index.php"));
    }
}
$actionUrl = $edit ? url_for("admin/neighborhoods/create.php?id=" . $id) : url_for("admin/neighborhoods/create.php");
$html = "<div class=\"admin-toolbar\"><h1>" . ($edit ? "Modifica quartiere" : "Nuovo quartiere") . "</h1></div>";
if ($errors !== []) {
    $html .= "<div class=\"alert alert-danger\"><ul>";
    foreach ($errors as $err) $html .= "<li>" . e($err) . "</li>";
    $html .= "</ul></div>";
}
$html .= "<section class=\"panel form-panel\"><form class=\"admin-form\" method=\"post\" action=\"" . e($actionUrl) . "\">"
    . csrf_field("neighborhood_form")
    . "<label>Codice<input type=\"text\" name=\"code\" value=\"" . e((string) $data["code"]) . "\" required></label>"
    . "<label>Nome<input type=\"text\" name=\"name\" value=\"" . e((string) $data["name"]) . "\" required></label>"
    . "<label>Descrizione<textarea name=\"description\" rows=\"3\">" . e((string) $data["description"]) . "</textarea></label>"
    . "<div class=\"form-actions\"><button class=\"button-primary\" type=\"submit\">" . ($edit ? "Salva modifiche" : "Crea quartiere") . "</button> "
    . "<a href=\"" . e(url_for("admin/neighborhoods/index.php")) . "\">Annulla</a></div>"
    . "</form></section>";
render_admin_page($edit ? "Modifica quartiere" : "Nuovo quartiere", $html, "admin.neighborhoods.index");
