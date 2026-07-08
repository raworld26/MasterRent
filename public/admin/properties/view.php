<?php
declare(strict_types=1);
require_once __DIR__ . "/../../../includes/bootstrap.php";
require_login();
require_service("admin.properties.index");
$id = (int) query_str("id");
$property = property_find($id);
if ($property === null) {
    set_flash("danger", "Annuncio non trovato.");
    redirect(url_for("admin/properties/index.php"));
}
$canManage = has_service("admin.properties.manage");

if ($canManage && ($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST") {
    if (!verify_csrf_token((string) ($_POST["csrf_token"] ?? ""), "property_view")) {
        set_flash("danger", "Token CSRF non valido.");
    } else {
        $action = post_str("action");
        if ($action === "delete_image") {
            $imgId = post_int("image_id");
            $img = property_find_image($imgId);
            if ($img && (int)$img["property_id"] === $id) {
                delete_uploaded_image_file($img["filename"]);
                property_delete_image($imgId);
                set_flash("success", "Immagine eliminata.");
            }
        } elseif ($action === "set_cover") {
            property_set_cover($id, post_int("image_id"));
            set_flash("success", "Copertina aggiornata.");
        }
    }
    redirect(url_for("admin/properties/view.php?id=" . $id));
}

$rooms = rooms_by_property($id);
$images = property_images($id);
$poles = property_poles($id);

$html = "<div class=\"admin-toolbar\"><h1>Dettaglio Annuncio</h1>";
if ($canManage) {
    $html .= "<a class=\"button-primary\" href=\"" . e(url_for("admin/properties/form.php?id=" . $id)) . "\">Modifica annuncio</a>";
}
$html .= "</div>";

$html .= "<section class=\"panel\">"
    . "<h2>" . e($property["title"]) . "</h2>"
    . "<p><strong>Proprietario:</strong> " . e($property["landlord_first"] . " " . $property["landlord_last"]) . " (" . e($property["landlord_email"]) . ")<br>"
    . "<strong>Indirizzo:</strong> " . e($property["address"]) . " " . e((string)$property["house_number"]) . ", " . e($property["neighborhood_name"]) . "<br>"
    . "<strong>Info:</strong> Vani: " . (int)$property["total_rooms"] . ", Riscaldamento: " . e($property["heating_type"]) . ((int)$property["has_elevator"] ? ", Ascensore" : "") . "</p>"
    . "<p>" . nl2br(e((string)$property["description"])) . "</p>"
    . "</section>";

$html .= "<div class=\"admin-toolbar\" style=\"margin-top:2rem;\"><h2>Stanze</h2>";
if ($canManage) {
    $html .= "<a class=\"button-primary\" href=\"" . e(url_for("admin/rooms/form.php?property_id=" . $id)) . "\">+ Aggiungi stanza</a>";
}
$html .= "</div><section class=\"panel\">";
if ($rooms === []) {
    $html .= "<p class=\"muted\">Nessuna stanza presente.</p>";
} else {
    $html .= "<table class=\"data-table\"><thead><tr><th>Nome</th><th>Tipo</th><th>Prezzo</th><th>Stato</th><th>Azioni</th></tr></thead><tbody>";
    foreach ($rooms as $r) {
        $actions = $canManage ? "<a class=\"button-small button-secondary\" href=\"" . e(url_for("admin/rooms/form.php?id=" . $r["id"])) . "\">Modifica</a>" : "";
        $html .= "<tr><td>" . e($r["name"]) . "</td><td>" . e(room_type_label($r["type"])) . "</td><td>" . e(format_price($r["price_monthly"])) . "</td><td>" . room_status_badge($r["status"]) . "</td><td>" . $actions . "</td></tr>";
    }
    $html .= "</tbody></table>";
}
$html .= "</section>";

$html .= "<div class=\"admin-toolbar\" style=\"margin-top:2rem;\"><h2>Immagini</h2></div><section class=\"panel\">";
if ($images === []) {
    $html .= "<p class=\"muted\">Nessuna immagine presente.</p>";
} else {
    $html .= "<div style=\"display:grid;grid-template-columns:repeat(auto-fill, minmax(200px, 1fr));gap:1rem;\">";
    foreach ($images as $img) {
        $html .= "<div style=\"border:1px solid #ddd;padding:0.5rem;\">"
            . property_image_markup($img["filename"], "width:100%;height:150px;object-fit:cover;")
            . "<div style=\"margin-top:0.5rem;display:flex;gap:0.5rem;align-items:center;\">";
        if ((int)$img["is_cover"]) {
            $html .= "<span class=\"status-pill\">Copertina</span>";
        } elseif ($canManage) {
            $html .= "<form method=\"post\" action=\"\">" . csrf_field("property_view") . "<input type=\"hidden\" name=\"action\" value=\"set_cover\"><input type=\"hidden\" name=\"image_id\" value=\"" . $img["id"] . "\"><button type=\"submit\" class=\"button-small button-secondary\">Rendi copertina</button></form>";
        }
        if ($canManage) {
            $html .= "<form method=\"post\" action=\"\" onsubmit=\"return confirm('Eliminare immagine?')\">" . csrf_field("property_view") . "<input type=\"hidden\" name=\"action\" value=\"delete_image\"><input type=\"hidden\" name=\"image_id\" value=\"" . $img["id"] . "\"><button type=\"submit\" class=\"button-small button-danger\">Elimina</button></form>";
        }
        $html .= "</div></div>";
    }
    $html .= "</div>";
}
$html .= "</section>";

$html .= "<div class=\"admin-toolbar\" style=\"margin-top:2rem;\"><h2>Distanze dai poli</h2></div><section class=\"panel\">";
if ($poles === []) {
    $html .= "<p class=\"muted\">Nessuna distanza configurata.</p>";
} else {
    $html .= "<table class=\"data-table\"><thead><tr><th>Polo</th><th>Distanza (min)</th><th>Mezzo</th></tr></thead><tbody>";
    foreach ($poles as $p) {
        $html .= "<tr><td>" . e($p["pole_name"]) . "</td><td>" . (int)$p["distance_minutes"] . "</td><td>" . e(transit_type_label($p["transit_type"])) . "</td></tr>";
    }
    $html .= "</tbody></table>";
}
$html .= "</section>";

render_admin_page("Dettaglio: " . $property["title"], $html, "admin.properties.index");
