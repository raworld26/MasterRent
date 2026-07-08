<?php
declare(strict_types=1);
require_once __DIR__ . "/../../../includes/bootstrap.php";
require_login();
require_service("admin.properties.manage");
$id = (int) query_str("id");
$edit = $id > 0;
$propertyId = (int) query_str("property_id");
$data = ["name" => "", "type" => "single", "price_monthly" => "", "deposit_months" => 2, "expenses_included" => 0, "contract_type" => "Transitorio Studenti", "is_available" => 1];
$selectedAmenityIds = [];
if ($edit) {
    $row = room_find($id);
    if ($row === null) {
        set_flash("danger", "Stanza non trovata.");
        redirect(url_for("admin/properties/index.php"));
    }
    $data = $row;
    $propertyId = (int)$row["property_id"];
    $selectedAmenityIds = room_amenity_ids($id);
} else {
    if (property_find($propertyId) === null) {
        set_flash("danger", "Immobile non valido.");
        redirect(url_for("admin/properties/index.php"));
    }
}
$errors = [];
if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") {
    if (!verify_csrf_token((string) ($_POST["csrf_token"] ?? ""), "room_form")) {
        $errors[] = "Token CSRF non valido.";
    }
    $action = post_str("action");
    if ($edit && $action === "delete") {
        room_delete($id);
        property_refresh_room_count($propertyId);
        set_flash("success", "Stanza eliminata.");
        redirect(url_for("admin/properties/view.php?id=" . $propertyId));
    }

    $data["name"] = post_str("name");
    $data["type"] = post_str("type");
    $data["price_monthly"] = post_str("price_monthly");
    $data["deposit_months"] = post_int("deposit_months");
    $data["expenses_included"] = isset($_POST["expenses_included"]) ? 1 : 0;
    $data["contract_type"] = post_str("contract_type");
    $data["is_available"] = isset($_POST["is_available"]) ? 1 : 0;
    $selectedAmenityIds = isset($_POST["amenities"]) && is_array($_POST["amenities"]) ? array_map("intval", $_POST["amenities"]) : [];

    if ($data["name"] === "") $errors[] = "Nome obbligatorio.";
    if (!is_numeric($data["price_monthly"]) || (float)$data["price_monthly"] < 0) $errors[] = "Prezzo non valido.";

    if ($errors === []) {
        if ($edit) {
            room_update($id, $data);
            room_set_amenities($id, $selectedAmenityIds);
            property_refresh_room_count($propertyId);
            set_flash("success", "Stanza aggiornata.");
        } else {
            $data["property_id"] = $propertyId;
            $newId = room_create($data);
            room_set_amenities($newId, $selectedAmenityIds);
            property_refresh_room_count($propertyId);
            set_flash("success", "Stanza creata.");
        }
        redirect(url_for("admin/properties/view.php?id=" . $propertyId));
    }
}
$actionUrl = $edit ? url_for("admin/rooms/form.php?id=" . $id) : url_for("admin/rooms/form.php?property_id=" . $propertyId);
$html = "<div class=\"admin-toolbar\"><h1>" . ($edit ? "Modifica stanza" : "Nuova stanza") . "</h1></div>";
if ($errors !== []) {
    $html .= "<div class=\"alert alert-danger\"><ul>";
    foreach ($errors as $err) $html .= "<li>" . e($err) . "</li>";
    $html .= "</ul></div>";
}
$html .= "<section class=\"panel form-panel\"><form class=\"admin-form\" method=\"post\" action=\"" . e($actionUrl) . "\">"
    . csrf_field("room_form")
    . "<label>Nome<input type=\"text\" name=\"name\" value=\"" . e((string) $data["name"]) . "\" required></label>"
    . "<div style=\"display:grid;grid-template-columns:1fr 1fr;gap:1rem;\">"
    . "<label>Tipo<select name=\"type\" required>" . select_options([["id"=>"single","name"=>"Singola"], ["id"=>"double","name"=>"Doppia"], ["id"=>"bed_space","name"=>"Posto letto"], ["id"=>"entire_apartment","name"=>"Intero appartamento"]], $data["type"]) . "</select></label>"
    . "<label>Prezzo Mensile (€)<input type=\"number\" step=\"0.01\" min=\"0\" name=\"price_monthly\" value=\"" . e((string) $data["price_monthly"]) . "\" required></label>"
    . "</div>"
    . "<div style=\"display:grid;grid-template-columns:1fr 1fr;gap:1rem;\">"
    . "<label>Mensilità di caparra<input type=\"number\" min=\"1\" name=\"deposit_months\" value=\"" . (int) $data["deposit_months"] . "\" required></label>"
    . "<label>Tipo Contratto<select name=\"contract_type\" required>" . select_options([["id"=>"Transitorio Studenti","name"=>"Transitorio Studenti"], ["id"=>"Concordato","name"=>"Concordato"], ["id"=>"Libero Mercato","name"=>"Libero Mercato"], ["id"=>"Transitorio","name"=>"Transitorio"]], $data["contract_type"]) . "</select></label>"
    . "</div>"
    . "<div style=\"display:grid;grid-template-columns:1fr 1fr;gap:1rem;\">"
    . "<label><br><input type=\"checkbox\" name=\"expenses_included\" value=\"1\"" . checked_attr((bool)$data["expenses_included"]) . "> Spese incluse</label>"
    . "<label><br><input type=\"checkbox\" name=\"is_available\" value=\"1\"" . checked_attr((bool)$data["is_available"]) . "> Pubblica/Rendi disponibile</label>"
    . "</div>"
    . "<fieldset><legend>Accessori</legend><div style=\"display:flex;flex-wrap:wrap;gap:1rem;\">";
foreach (amenities_all() as $a) {
    $html .= "<label><input type=\"checkbox\" name=\"amenities[]\" value=\"" . $a["id"] . "\"" . checked_attr(in_array((int)$a["id"], $selectedAmenityIds, true)) . "> " . e($a["name"]) . "</label>";
}
$html .= "</div></fieldset>"
    . "<div class=\"form-actions\"><button class=\"button-primary\" type=\"submit\">" . ($edit ? "Salva modifiche" : "Crea stanza") . "</button> "
    . "<a href=\"" . e(url_for("admin/properties/view.php?id=" . $propertyId)) . "\">Annulla</a></div>"
    . "</form></section>";

if ($edit) {
    $html .= "<section class=\"panel\" style=\"margin-top:2rem;\"><h2>Zona Pericolo</h2>"
        . "<form method=\"post\" action=\"" . e($actionUrl) . "\" onsubmit=\"return confirm('Eliminare definitivamente questa stanza?')\">"
        . csrf_field("room_form") . "<input type=\"hidden\" name=\"action\" value=\"delete\">"
        . "<button type=\"submit\" class=\"button-danger\">Elimina stanza</button></form></section>";
}

render_admin_page($edit ? "Modifica stanza" : "Nuova stanza", $html, "admin.properties.index");
