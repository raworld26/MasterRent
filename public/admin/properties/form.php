<?php
declare(strict_types=1);
require_once __DIR__ . "/../../../includes/bootstrap.php";
require_login();
require_service("admin.properties.manage");
$id = (int) query_str("id");
$edit = $id > 0;
$data = ["title" => "", "landlord_id" => "", "neighborhood_id" => "", "address" => "", "house_number" => "", "postal_code" => "67100", "total_rooms" => 1, "description" => "", "has_elevator" => 0, "heating_type" => "autonomous"];
$initialPrice = "";
if ($edit) {
    $row = property_find($id);
    if ($row === null) {
        set_flash("danger", "Annuncio non trovato.");
        redirect(url_for("admin/properties/index.php"));
    }
    $data = $row;
}
$errors = [];
if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") {
    if (!verify_csrf_token((string) ($_POST["csrf_token"] ?? ""), "property_form")) {
        $errors[] = "Token CSRF non valido.";
    }
    $data["title"] = post_str("title");
    $data["landlord_id"] = post_str("landlord_id");
    $data["neighborhood_id"] = post_str("neighborhood_id");
    $data["address"] = post_str("address");
    $data["house_number"] = post_str("house_number");
    $data["postal_code"] = post_str("postal_code");
    $data["total_rooms"] = post_int("total_rooms");
    $data["description"] = post_str("description");
    $data["has_elevator"] = isset($_POST["has_elevator"]) ? 1 : 0;
    $data["heating_type"] = post_str("heating_type");
    $initialPrice = post_str("price_monthly");

    if ($data["title"] === "") $errors[] = "Titolo obbligatorio.";
    if ($data["landlord_id"] === "") $errors[] = "Proprietario obbligatorio.";
    if ($data["neighborhood_id"] === "") $errors[] = "Quartiere obbligatorio.";
    if ($data["address"] === "") $errors[] = "Indirizzo obbligatorio.";
    if (!$edit && ($initialPrice === "" || !is_numeric($initialPrice) || (float) $initialPrice < 0)) $errors[] = "Prezzo mensile non valido.";

    if ($errors === []) {
        if ($edit) {
            property_update($id, $data);
            property_set_landlord($id, (int)$data["landlord_id"]);
            set_flash("success", "Annuncio aggiornato.");
            redirect(url_for("admin/properties/view.php?id=" . $id));
        } else {
            $newId = property_create($data);
            room_create([
                "property_id" => $newId,
                "name" => (string) $data["title"],
                "type" => "single",
                "price_monthly" => number_format((float) $initialPrice, 2, ".", ""),
                "deposit_months" => 2,
                "expenses_included" => 0,
                "contract_type" => "Transitorio Studenti",
                "is_available" => 1,
            ]);
            property_refresh_room_count($newId);
            set_flash("success", "Annuncio creato con una soluzione iniziale.");
            redirect(url_for("admin/properties/view.php?id=" . $newId));
        }
    }
}
$actionUrl = $edit ? url_for("admin/properties/form.php?id=" . $id) : url_for("admin/properties/form.php");
$html = "<div class=\"admin-toolbar\"><h1>" . ($edit ? "Modifica annuncio" : "Nuovo annuncio") . "</h1></div>";
if ($errors !== []) {
    $html .= "<div class=\"alert alert-danger\"><ul>";
    foreach ($errors as $err) $html .= "<li>" . e($err) . "</li>";
    $html .= "</ul></div>";
}
$html .= "<section class=\"panel form-panel\"><form class=\"admin-form\" method=\"post\" action=\"" . e($actionUrl) . "\">"
    . csrf_field("property_form")
    . "<label>Titolo<input type=\"text\" name=\"title\" value=\"" . e((string) $data["title"]) . "\" required></label>"
    . "<div style=\"display:grid;grid-template-columns:1fr 1fr;gap:1rem;\">"
    . "<label>Proprietario<select name=\"landlord_id\" required>" . select_options(landlords_for_select(), $data["landlord_id"], "id", "name", "— Seleziona —") . "</select></label>"
    . "<label>Quartiere<select name=\"neighborhood_id\" required>" . select_options(neighborhoods_all(), $data["neighborhood_id"], "id", "name", "— Seleziona —") . "</select></label>"
    . "</div>"
    . (!$edit ? "<label>Prezzo mensile iniziale (&euro;)<input type=\"number\" step=\"0.01\" min=\"0\" name=\"price_monthly\" value=\"" . e($initialPrice) . "\" required></label>" : "")
    . "<div style=\"display:grid;grid-template-columns:2fr 1fr 1fr;gap:1rem;\">"
    . "<label>Indirizzo<input type=\"text\" name=\"address\" value=\"" . e((string) $data["address"]) . "\" required></label>"
    . "<label>Civico<input type=\"text\" name=\"house_number\" value=\"" . e((string) $data["house_number"]) . "\"></label>"
    . "<label>CAP<input type=\"text\" name=\"postal_code\" value=\"" . e((string) $data["postal_code"]) . "\"></label>"
    . "</div>"
    . "<div style=\"display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;\">"
    . "<label>Totale vani<input type=\"number\" name=\"total_rooms\" value=\"" . (int) $data["total_rooms"] . "\"></label>"
    . "<label>Riscaldamento<select name=\"heating_type\">" . select_options([["id"=>"autonomous","name"=>"Autonomo"], ["id"=>"centralized","name"=>"Centralizzato"], ["id"=>"independent","name"=>"Indipendente"]], $data["heating_type"]) . "</select></label>"
    . "<label><br><input type=\"checkbox\" name=\"has_elevator\" value=\"1\"" . checked_attr((bool)$data["has_elevator"]) . "> Ascensore</label>"
    . "</div>"
    . "<label>Descrizione<textarea name=\"description\" rows=\"4\">" . e((string) $data["description"]) . "</textarea></label>"
    . "<div class=\"form-actions\"><button class=\"button-primary\" type=\"submit\">" . ($edit ? "Salva modifiche" : "Crea annuncio") . "</button> "
    . "<a href=\"" . e(url_for($edit ? "admin/properties/view.php?id=" . $id : "admin/properties/index.php")) . "\">Annulla</a></div>"
    . "</form></section>";
render_admin_page($edit ? "Modifica annuncio" : "Nuovo annuncio", $html, "admin.properties.index");
