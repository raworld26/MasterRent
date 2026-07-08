<?php
declare(strict_types=1);
require_once __DIR__ . "/../../../includes/bootstrap.php";
require_login();
require_service("admin.services.manage");
$id = (int) query_str("id");
$edit = $id > 0;
$data = ["code" => "", "name" => "", "description" => "", "area" => "backend", "path" => "", "http_method" => "GET", "is_menu_item" => 0, "menu_order" => 0, "is_active" => 1];
$selectedGroups = [];
if ($edit) {
    $row = service_find($id);
    if ($row === null) {
        set_flash("danger", "Servizio non trovato.");
        redirect(url_for("admin/services/index.php"));
    }
    $data = $row;
    $selectedGroups = service_group_ids($id);
}
$errors = [];
if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") {
    if (!verify_csrf_token((string) ($_POST["csrf_token"] ?? ""), "service_form")) {
        $errors[] = "Token CSRF non valido.";
    }
    $data["code"] = post_str("code");
    $data["name"] = post_str("name");
    $data["description"] = post_str("description");
    $data["area"] = post_str("area");
    $data["path"] = post_str("path");
    $data["http_method"] = post_str("http_method");
    $data["is_menu_item"] = isset($_POST["is_menu_item"]) ? 1 : 0;
    $data["menu_order"] = post_int("menu_order");
    $data["is_active"] = isset($_POST["is_active"]) ? 1 : 0;
    $selectedGroups = isset($_POST["groups"]) && is_array($_POST["groups"]) ? array_map("intval", $_POST["groups"]) : [];

    if ($data["code"] === "") $errors[] = "Il codice è obbligatorio.";
    if ($data["name"] === "") $errors[] = "Il nome è obbligatorio.";
    if ($data["code"] !== "" && service_code_exists($data["code"], $edit ? $id : 0)) {
        $errors[] = "Il codice è già in uso.";
    }

    if ($errors === []) {
        if ($edit) {
            service_update($id, $data);
            service_set_groups($id, $selectedGroups);
            set_flash("success", "Servizio aggiornato.");
        } else {
            $newId = service_create($data);
            service_set_groups($newId, $selectedGroups);
            set_flash("success", "Servizio creato.");
        }
        redirect(url_for("admin/services/index.php"));
    }
}
$actionUrl = $edit ? url_for("admin/services/create.php?id=" . $id) : url_for("admin/services/create.php");
$html = "<div class=\"admin-toolbar\"><h1>" . ($edit ? "Modifica servizio" : "Nuovo servizio") . "</h1></div>";
if ($errors !== []) {
    $html .= "<div class=\"alert alert-danger\"><ul>";
    foreach ($errors as $err) $html .= "<li>" . e($err) . "</li>";
    $html .= "</ul></div>";
}
$html .= "<section class=\"panel form-panel\"><form class=\"admin-form\" method=\"post\" action=\"" . e($actionUrl) . "\">"
    . csrf_field("service_form")
    . "<div style=\"display:grid;grid-template-columns:1fr 1fr;gap:1rem;\">"
    . "<label>Codice<input type=\"text\" name=\"code\" value=\"" . e((string) $data["code"]) . "\" required></label>"
    . "<label>Nome<input type=\"text\" name=\"name\" value=\"" . e((string) $data["name"]) . "\" required></label>"
    . "</div>"
    . "<div style=\"display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;\">"
    . "<label>Area<select name=\"area\">" . select_options([["id"=>"backend","name"=>"Backend"], ["id"=>"frontend","name"=>"Frontend"], ["id"=>"api","name"=>"API"]], $data["area"]) . "</select></label>"
    . "<label>Path (URL parziale)<input type=\"text\" name=\"path\" value=\"" . e((string) $data["path"]) . "\"></label>"
    . "<label>Metodo HTTP<select name=\"http_method\">" . select_options([["id"=>"GET","name"=>"GET"], ["id"=>"POST","name"=>"POST"], ["id"=>"*","name"=>"Qualsiasi (*)"]], $data["http_method"]) . "</select></label>"
    . "</div>"
    . "<div style=\"display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;\">"
    . "<label><br><input type=\"checkbox\" name=\"is_menu_item\" value=\"1\"" . checked_attr((bool)$data["is_menu_item"]) . "> Mostra nel menu</label>"
    . "<label>Ordine Menu<input type=\"number\" name=\"menu_order\" value=\"" . (int) $data["menu_order"] . "\"></label>"
    . "<label><br><input type=\"checkbox\" name=\"is_active\" value=\"1\"" . checked_attr((bool)$data["is_active"]) . "> Attivo</label>"
    . "</div>"
    . "<label>Descrizione<textarea name=\"description\" rows=\"2\">" . e((string) $data["description"]) . "</textarea></label>"
    . "<fieldset><legend>Gruppi associati</legend><div style=\"display:flex;flex-wrap:wrap;gap:1rem;\">";
foreach (groups_all() as $g) {
    $html .= "<label><input type=\"checkbox\" name=\"groups[]\" value=\"" . $g["id"] . "\"" . checked_attr(in_array((int)$g["id"], $selectedGroups, true)) . "> " . e($g["name"]) . "</label>";
}
$html .= "</div></fieldset>"
    . "<div class=\"form-actions\"><button class=\"button-primary\" type=\"submit\">" . ($edit ? "Salva modifiche" : "Crea servizio") . "</button> "
    . "<a href=\"" . e(url_for("admin/services/index.php")) . "\">Annulla</a></div>"
    . "</form></section>";
render_admin_page($edit ? "Modifica servizio" : "Nuovo servizio", $html, "admin.services.index");
